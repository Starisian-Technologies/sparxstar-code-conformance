#!/usr/bin/env bash
#
# set-repo-credentials.sh: set the repository-level credentials a PRIVATE repo
# needs, then verify them by name.
#
# The list of names, their stores and when each is needed is defined ONLY in
# cross-repo/cross-repo-access-and-installation.md ("Secrets and variables" and
# "Adding a private repo"). If this script and that doc disagree, the doc wins:
# fix the script. The script checks its names against the doc when the doc sits
# next to it.
#
# Run by the org owner with their own authenticated GitHub CLI session.
# Secret values are read from files or a hidden prompt and passed to `gh` on
# standard input. They are never put on a command line, echoed, logged or
# written to disk, and they are never read back: verification lists names only.
#
# Usage: see `set-repo-credentials.sh --help`.

set -euo pipefail
umask 077

# ---------------------------------------------------------------------------
# Names (from cross-repo/cross-repo-access-and-installation.md).
# ---------------------------------------------------------------------------
readonly DEFAULT_OWNER="Starisian-Technologies"
readonly CR_CLIENT_ID_VAR="COMPOSER_RESOLVER_CLIENT_ID"
readonly CR_KEY_SECRET="COMPOSER_RESOLVER_PRIVATE_KEY"
readonly CS_CLIENT_ID_VAR="CONTRACT_SYNC_CLIENT_ID"
readonly CS_KEY_SECRET="CONTRACT_SYNC_PRIVATE_KEY"
readonly ANTHROPIC_SECRET="ANTHROPIC_API_KEY"

readonly SCRIPT_NAME="${0##*/}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly SCRIPT_DIR
readonly CANONICAL_DOC="${SCRIPT_DIR}/cross-repo-access-and-installation.md"

usage() {
	cat <<EOF
Usage:
  ${SCRIPT_NAME} [options] \\
    --composer-resolver-client-id <CLIENT_ID> \\
    --composer-resolver-key <path/to/composer-resolver.pem> \\
    --contract-sync-client-id <CLIENT_ID> \\
    --contract-sync-key <path/to/contract-sync.pem> \\
    <repo> [<repo> ...]

Sets, at repository level, on each named PRIVATE repo:
  Actions variable    ${CR_CLIENT_ID_VAR}, ${CS_CLIENT_ID_VAR}
  Actions secret      ${CR_KEY_SECRET}, ${CS_KEY_SECRET}
  Dependabot secret   ${CR_KEY_SECRET}, ${CS_KEY_SECRET}
  Actions secret      ${ANTHROPIC_SECRET} (only with --anthropic-key; hidden prompt)
then lists each store's names and reports any expected name that is missing.

<repo> is a repo name (owner defaults to ${DEFAULT_OWNER}) or OWNER/NAME.
Client IDs are the Client ID strings (Iv23...), never the numeric App ID.

Options:
  --dry-run                       Check inputs and repos, show what would be set,
                                  and verify current names. Changes nothing.
  --anthropic-key                 Also set ${ANTHROPIC_SECRET}. Prompts once,
                                  input hidden, and uses the value for every repo.
  --no-dependabot                 Skip both Dependabot copies (Dependabot does
                                  not run in these repos).
  --no-contract-sync-dependabot   Skip the Dependabot copy of ${CS_KEY_SECRET}
                                  (the repos write no drift record).
  -h, --help                      Show this help.

Requires: gh, logged in as the org owner (gh auth login).
EOF
}

die() {
	printf '%s: error: %s\n' "${SCRIPT_NAME}" "$*" >&2
	exit 1
}
warn() { printf '%s: warning: %s\n' "${SCRIPT_NAME}" "$*" >&2; }
info() { printf '%s\n' "$*"; }

# ---------------------------------------------------------------------------
# Arguments.
# ---------------------------------------------------------------------------
dry_run=0
with_anthropic=0
dependabot=1
cs_dependabot=1
cr_client_id=""
cs_client_id=""
cr_key=""
cs_key=""
repos=()

need_value() {
	[[ $# -ge 2 && -n "$2" && "$2" != --* ]] || die "$1 needs a value (see --help)"
}

while [[ $# -gt 0 ]]; do
	case "$1" in
	--dry-run) dry_run=1 ;;
	--anthropic-key) with_anthropic=1 ;;
	--no-dependabot) dependabot=0 ;;
	--no-contract-sync-dependabot) cs_dependabot=0 ;;
	--composer-resolver-client-id) need_value "$@"; cr_client_id="$2"; shift ;;
	--composer-resolver-key) need_value "$@"; cr_key="$2"; shift ;;
	--contract-sync-client-id) need_value "$@"; cs_client_id="$2"; shift ;;
	--contract-sync-key) need_value "$@"; cs_key="$2"; shift ;;
	-h | --help) usage; exit 0 ;;
	--) shift; repos+=("$@"); break ;;
	-*) die "unknown option: $1 (see --help)" ;;
	*) repos+=("$1") ;;
	esac
	shift
done

[[ ${#repos[@]} -gt 0 ]] || die "name at least one repo (see --help)"
[[ -n "$cr_client_id" ]] || die "--composer-resolver-client-id is required"
[[ -n "$cs_client_id" ]] || die "--contract-sync-client-id is required"
[[ -n "$cr_key" ]] || die "--composer-resolver-key is required"
[[ -n "$cs_key" ]] || die "--contract-sync-key is required"

check_client_id() {
	local label="$1" value="$2"
	if [[ "$value" =~ ^[0-9]+$ ]]; then
		die "${label} looks like a numeric App ID. Pass the Client ID string (Iv23...) instead"
	fi
	if [[ ! "$value" =~ ^[A-Za-z0-9._-]+$ ]]; then
		die "${label} contains characters a Client ID does not have"
	fi
}
check_client_id "--composer-resolver-client-id" "$cr_client_id"
check_client_id "--contract-sync-client-id" "$cs_client_id"

# Inspect the key files without ever printing their contents.
check_pem() {
	local label="$1" path="$2"
	[[ -f "$path" && -r "$path" ]] || die "${label}: cannot read file ${path}"
	[[ -s "$path" ]] || die "${label}: file is empty: ${path}"
	grep -q -- '-----BEGIN [A-Z ]*PRIVATE KEY-----' "$path" ||
		die "${label}: ${path} is not a PEM private key"
}
check_pem "--composer-resolver-key" "$cr_key"
check_pem "--contract-sync-key" "$cs_key"
[[ "$(cd "$(dirname "$cr_key")" && pwd)/$(basename "$cr_key")" != \
	"$(cd "$(dirname "$cs_key")" && pwd)/$(basename "$cs_key")" ]] ||
	die "--composer-resolver-key and --contract-sync-key point to the same file"

# ---------------------------------------------------------------------------
# Drift guard: every name this script uses must appear in the canonical doc.
# ---------------------------------------------------------------------------
if [[ -f "$CANONICAL_DOC" ]]; then
	for name in "$CR_CLIENT_ID_VAR" "$CR_KEY_SECRET" "$CS_CLIENT_ID_VAR" "$CS_KEY_SECRET" "$ANTHROPIC_SECRET"; do
		grep -q -- "\`${name}\`" "$CANONICAL_DOC" ||
			die "${name} is not in ${CANONICAL_DOC}. The doc wins: update this script to match it"
	done
else
	warn "canonical doc not found next to the script; cannot check names against it"
fi

# ---------------------------------------------------------------------------
# GitHub CLI session.
# ---------------------------------------------------------------------------
command -v gh >/dev/null 2>&1 ||
	die "GitHub CLI (gh) is not installed. Install it, run 'gh auth login', then re-run"
if ! gh auth status >/dev/null 2>&1; then
	die "gh is not logged in. Run 'gh auth login' as the org owner, then re-run"
fi

# ---------------------------------------------------------------------------
# Optional Anthropic key: hidden prompt, held in memory only.
# ---------------------------------------------------------------------------
anthropic_key=""
cleanup() { anthropic_key=""; unset anthropic_key; }
trap cleanup EXIT

if [[ $with_anthropic -eq 1 && $dry_run -eq 0 ]]; then
	[[ -t 0 ]] || die "--anthropic-key needs an interactive terminal for the hidden prompt"
	IFS= read -r -s -p "${ANTHROPIC_SECRET} (input hidden): " anthropic_key
	printf '\n'
	[[ -n "$anthropic_key" ]] || die "${ANTHROPIC_SECRET} is empty"
fi

# ---------------------------------------------------------------------------
# Helpers that touch GitHub.
# ---------------------------------------------------------------------------
run() {
	# Prints the action, never a value. Values reach gh on stdin only.
	local what="$1"
	shift
	if [[ $dry_run -eq 1 ]]; then
		info "  [dry-run] would set ${what}"
		return 0
	fi
	if "$@" >/dev/null; then
		info "  set ${what}"
	else
		warn "failed to set ${what}"
		failures=$((failures + 1))
	fi
}

set_variable() { # repo name value(non-secret Client ID)
	run "Actions variable $2" gh variable set "$2" --repo "$1" --body "$3"
}

set_secret_from_file() { # repo app name path
	run "${2^} secret $3" gh secret set "$3" --repo "$1" --app "$2" <"$4"
}

set_anthropic() { # repo
	if [[ $dry_run -eq 1 ]]; then
		info "  [dry-run] would set Actions secret ${ANTHROPIC_SECRET} (from hidden prompt)"
		return 0
	fi
	# printf is a shell builtin, so the value is never on a process command line.
	if printf '%s' "$anthropic_key" | gh secret set "$ANTHROPIC_SECRET" --repo "$1" --app actions >/dev/null; then
		info "  set Actions secret ${ANTHROPIC_SECRET}"
	else
		warn "failed to set Actions secret ${ANTHROPIC_SECRET}"
		failures=$((failures + 1))
	fi
}

list_names() { # kind(variable|actions|dependabot) repo
	case "$1" in
	variable) gh variable list --repo "$2" --json name --jq '.[].name' ;;
	*) gh secret list --repo "$2" --app "$1" --json name --jq '.[].name' ;;
	esac
}

# Notes from the repo's own files about which optional copies the doc requires.
inspect_repo() { # repo
	local repo="$1" path content
	if gh api "repos/${repo}/contents/.github/dependabot.yml" >/dev/null 2>&1 ||
		gh api "repos/${repo}/contents/.github/dependabot.yaml" >/dev/null 2>&1; then
		info "  note: .github/dependabot.yml found, so the Dependabot copies are required"
		[[ $dependabot -eq 1 ]] || warn "${repo} runs Dependabot but --no-dependabot was given"
	else
		info "  note: no .github/dependabot.yml; the Dependabot copies are optional here"
	fi
	while IFS= read -r path; do
		[[ -n "$path" ]] || continue
		content="$(gh api -H 'Accept: application/vnd.github.raw' "repos/${repo}/contents/${path}" 2>/dev/null || true)"
		if grep -Eq 'write_drift_record:[[:space:]]*false' <<<"$content"; then
			info "  note: ${path} sets write_drift_record: false (no drift record);"
			info "        the Dependabot copy of ${CS_KEY_SECRET} is optional here"
		fi
	done < <(gh api "repos/${repo}/contents/.github/workflows" \
		--jq '.[] | select(.name | test("\\.ya?ml$")) | .path' 2>/dev/null || true)
}

verify_repo() { # repo; sets repo_missing
	local repo="$1" kind name missing=0 names
	local -a expected
	for kind in variable actions dependabot; do
		case "$kind" in
		variable) expected=("$CR_CLIENT_ID_VAR" "$CS_CLIENT_ID_VAR") ;;
		actions)
			expected=("$CR_KEY_SECRET" "$CS_KEY_SECRET")
			[[ $with_anthropic -eq 1 ]] && expected+=("$ANTHROPIC_SECRET")
			;;
		dependabot)
			expected=()
			[[ $dependabot -eq 1 ]] && expected+=("$CR_KEY_SECRET")
			[[ $dependabot -eq 1 && $cs_dependabot -eq 1 ]] && expected+=("$CS_KEY_SECRET")
			;;
		esac
		[[ ${#expected[@]} -gt 0 ]] || continue
		if ! names="$(list_names "$kind" "$repo")"; then
			warn "${repo}: could not list ${kind} names"
			missing=$((missing + ${#expected[@]}))
			continue
		fi
		for name in "${expected[@]}"; do
			if grep -qxF -- "$name" <<<"$names"; then
				info "  ok       ${kind}: ${name}"
			else
				info "  MISSING  ${kind}: ${name}"
				missing=$((missing + 1))
			fi
		done
	done
	if [[ $with_anthropic -eq 0 ]] && grep -qxF -- "$ANTHROPIC_SECRET" <<<"$(list_names actions "$repo" 2>/dev/null || true)"; then
		info "  info     actions: ${ANTHROPIC_SECRET} is present (not changed)"
	fi
	repo_missing=$missing
}

# ---------------------------------------------------------------------------
# Main loop.
# ---------------------------------------------------------------------------
failures=0
total_missing=0
skipped=()

[[ $dry_run -eq 1 ]] && info "Dry run: nothing will be changed."

for raw in "${repos[@]}"; do
	if [[ "$raw" == */* ]]; then repo="$raw"; else repo="${DEFAULT_OWNER}/${raw}"; fi
	[[ "$repo" =~ ^[A-Za-z0-9._-]+/[A-Za-z0-9._-]+$ ]] || die "not a valid repo name: ${raw}"
	info ""
	info "== ${repo}"

	if ! visibility="$(gh repo view "$repo" --json visibility --jq '.visibility' 2>/dev/null)"; then
		warn "${repo}: not found, or this gh session cannot see it. Skipped"
		skipped+=("$repo")
		continue
	fi
	if [[ "$visibility" == "PUBLIC" ]]; then
		warn "${repo} is public. Public repos read the org-level values and must not get copies. Skipped"
		skipped+=("$repo")
		continue
	fi
	info "  visibility: ${visibility,,}"

	inspect_repo "$repo"

	set_variable "$repo" "$CR_CLIENT_ID_VAR" "$cr_client_id"
	set_secret_from_file "$repo" actions "$CR_KEY_SECRET" "$cr_key"
	set_variable "$repo" "$CS_CLIENT_ID_VAR" "$cs_client_id"
	set_secret_from_file "$repo" actions "$CS_KEY_SECRET" "$cs_key"
	if [[ $dependabot -eq 1 ]]; then
		set_secret_from_file "$repo" dependabot "$CR_KEY_SECRET" "$cr_key"
		if [[ $cs_dependabot -eq 1 ]]; then
			set_secret_from_file "$repo" dependabot "$CS_KEY_SECRET" "$cs_key"
		fi
	fi
	if [[ $with_anthropic -eq 1 ]]; then
		set_anthropic "$repo"
	fi

	info "  verify (names only):"
	repo_missing=0
	verify_repo "$repo"
	total_missing=$((total_missing + repo_missing))
done

cleanup

info ""
info "Manual steps this script does not do (see the canonical doc, \"Adding a private repo\"):"
info "  1. Scope the composer-resolver App to every repo each new repo's workflows read."
info "  2. Scope the contract-sync App to every repo they write to, including the drift-record"
info "     destination named under \"Drift records\"."
info "  3. Add each repo to \"Private repos carrying repository-level credentials\"."
info "     The script does not edit the doc."

if [[ ${#skipped[@]} -gt 0 ]]; then
	warn "skipped: ${skipped[*]}"
fi
if [[ $failures -gt 0 || $total_missing -gt 0 ]]; then
	if [[ $dry_run -eq 1 ]]; then
		info ""
		info "Dry run: ${total_missing} expected name(s) not present yet."
		exit 0
	fi
	die "${failures} set failure(s), ${total_missing} expected name(s) missing after setting"
fi
[[ ${#skipped[@]} -eq 0 ]] || exit 1
info ""
info "Done: every expected name is present."
