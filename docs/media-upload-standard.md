# Media Upload Standard

**Starisian Technologies — Audio, Video, and TUS Upload Implementation**

---

This document is the media and upload standard for Starisian Technologies. It governs all audio capture, video capture, file upload, and media processing.

All rules in the [Standards Handbook](standards-handbook.md) apply in full. This document adds media- and upload-specific requirements on top of them.

---

## Why These Limits Exist

These limits exist because bandwidth is a financial cost to users in constrained environments. Exceeding them is not a quality tradeoff — it is a billing impact on people who cannot absorb it. Every byte of audio or video that exceeds these limits has a real-world cost in data charges and battery consumption.

---

# 1. Audio Capture — Hard Limits

| Parameter | Limit | Rationale |
| :---- | :---- | :---- |
| Sample rate | 16,000 Hz maximum | Sufficient for voice. Higher rates waste bandwidth. |
| Channels | 1 (mono) | Stereo doubles the data with no voice quality gain. |
| Bitrate | 24 kbps target | Hard cap: 32 kbps. Opus/AAC-LC only. |
| Format | Opus or AAC-LC | No raw PCM. No WAV. Compressed only. |

## 1.1 Recording Duration Limits

| Mode | Max Duration |
| :---- | :---- |
| draft | 300 seconds |
| development | 180 seconds |
| Capture (production) | 120 seconds |

**Note:** This is a per-segment capture cap, not a limit on total recorded content. Each individual recording segment
captured through the standard intake interface is capped at 120 seconds. Long-form recordings (oral histories, elder
interviews) are produced by capturing multiple governed segments through a separately authorized async flow with
chunked upload — the cap prevents accidental runaway recording, not intentional cultural storytelling. See async
processing rules in the [Standards Handbook](standards-handbook.md).

| **FAIL** | `sampleRate > 16000` |
| :---- | :---- |
| **FAIL** | `channels > 1` |
| **FAIL** | `bitrate > 32000` |
| **FAIL** | format is WAV or uncompressed PCM |

## 1.2 Capture Surface — Input Device and Permissions

| Rule | Requirement |
| :---- | :---- |
| Permission request timing | (M) Only at the moment of user-initiated capture. Never on page load, never speculative. |
| Permission denial | (M) Handle `NotAllowedError` with a recoverable UI path (explain, link to OS settings, retry). |
| Device absence | (M) Handle `NotFoundError` and `OverconstrainedError` distinctly from denial. |
| Device enumeration | (M) Use `enumerateDevices()` only after permission is granted; do not fingerprint devices pre-consent. |
| Default input device | (M) Use the OS default unless the user explicitly selects another. |
| In-call interruptions | (M) Pause capture on `mediaSession` interruption signals (incoming call, focus loss). |
| Auto-start | (X) Never auto-start capture. Capture is always an explicit user action. |

| **FAIL** | `getUserMedia` called before explicit user action |
| :---- | :---- |
| **FAIL** | permission denial handled identically to device absence |
| **FAIL** | device enumeration called pre-consent |

## 1.3 Codec Parameters — Opus and AAC-LC

The 32 kbps cap and Opus/AAC-LC restriction are not the whole story. The following codec parameters are also enforced.

### Opus (preferred)

| Parameter | Value |
| :---- | :---- |
| Application | `voip` (latency- and intelligibility-tuned for speech) |
| Frame duration | 20 ms (`opusenc --framesize 20`) |
| Bit depth | 16-bit input (server resamples if higher) |
| Variable bitrate | Allowed; hard ceiling stays at 32 kbps |
| Forward error correction (FEC) | Enabled |
| Packet loss percentage hint | 5% (the SDK MAY raise this on detected loss) |
| Discontinuous transmission (DTX) | Enabled |

### AAC-LC (fallback)

| Parameter | Value |
| :---- | :---- |
| Profile | LC (Low Complexity) only — no HE-AAC, no AAC-LD |
| Bitrate mode | CBR or constrained-VBR |
| Frame size | 1024 samples |

| **FAIL** | Opus encoder configured for `audio` or `restricted_lowdelay` application |
| :---- | :---- |
| **FAIL** | Opus frame duration ≠ 20 ms |
| **FAIL** | AAC profile is HE-AAC, HE-AACv2, or AAC-LD |

## 1.4 Audio Chunking — Frame-Aligned Boundaries

Audio chunks for TUS upload MUST be aligned to encoded-packet boundaries. Splitting mid-packet produces undecodable chunks that the resume path cannot recover from cleanly.

- (M) Chunk boundary always falls on an Opus / AAC frame boundary.
- (M) Each chunk carries its own duration in metadata (`chunk_duration_ms`), independent of byte size.
- (M) Concatenating chunks in order MUST reproduce the original encoded stream byte-for-byte.
- (X) Splitting in the middle of an encoded frame.
- (X) Re-encoding on the client to fit a chunk boundary — adjust the chunk boundary instead.

| **FAIL** | concatenated chunks fail to decode end-to-end |
| :---- | :---- |
| **FAIL** | chunk metadata missing `chunk_duration_ms` |

---

# 2. Video Capture — Hard Limits

| Parameter | Limit | Rationale |
| :---- | :---- | :---- |
| Resolution | 640×480 maximum (VGA) | No HD. No 720p. No 1080p. |
| Frame rate | 15 fps maximum | Sufficient for documentation. Higher wastes bandwidth. |
| Bitrate | 500 kbps target | Hard cap: 800 kbps. |
| Codec | H.264 Baseline only | HEVC and AV1 forbidden unless fallback exists. |

## 2.1 Recording Duration Limits

| Mode | Max Duration |
| :---- | :---- |
| draft | 180 seconds |
| development | 120 seconds |
| production | 60 seconds |

| **FAIL** | width > 640 or height > 480 |
| :---- | :---- |
| **FAIL** | fps > 15 |
| **FAIL** | bitrate > 800 kbps |
| **FAIL** | codec is not H.264 Baseline |

---

# 3. JavaScript Capture Constraints

The following `getUserMedia` constraints are required in all capture implementations. They are not hints — they are enforced maximums.

```js
const constraints = {
  audio: {
    sampleRate: { ideal: 16000, max: 16000 },
    channelCount: { ideal: 1, max: 1 },
    echoCancellation: true,
    noiseSuppression: true,
  },
  video: {
    width:     { ideal: 640, max: 640 },
    height:    { ideal: 480, max: 480 },
    frameRate: { ideal: 15,  max: 15  },
  },
};

// Never start recording automatically — always require explicit user action
// Never assume camera or microphone availability — always handle NotAllowedError and NotFoundError
```

---

# 4. Audio Capture SDK Integration — Mandatory

All audio and video capture must use the approved audio capture SDK. Direct `MediaRecorder` usage in product code is forbidden.

- (M) All recording initiated through the SDK's `startRecording(constraints)` entry point
- (M) All recording stopped through the SDK's `stopRecording()` entry point
- (X) Direct `new MediaRecorder(stream)` in product code
- (X) Custom bitrate negotiation bypassing SDK-enforced limits

---

# 5. TUS Upload Server Standards

TUS (tus.io) is the required protocol for all file uploads. No full-file upload endpoints are permitted.

## 5.1 Chunk Limits

| Parameter | Limit |
| :---- | :---- |
| Max chunk size | 512 KB |
| Max total upload | 5 MB (hard cap enforced at PHP/server layer) |
| Retry attempts | 3 minimum with exponential backoff |
| Checksum verification | Required per chunk (SHA-256) |
| UUID assignment | Required per upload — server-generated |
| Final size validation | Required before write is committed |

## 5.2 Resume Guarantee

All uploads must support resume. A failed upload at any chunk must be resumable without restarting from zero. No full-file upload endpoints are permitted.

- Client stores upload offset in IndexedDB — survives page reload
- Server stores upload state and committed chunks — survives server restart within TTL
- TUS `PATCH` requests are idempotent — same chunk submitted twice produces no duplicate write

| **FAIL** | upload endpoint without chunking support |
| :---- | :---- |
| **FAIL** | chunk > 512 KB |
| **FAIL** | upload without checksum verification |
| **FAIL** | upload without UUID |
| **FAIL** | full-file upload endpoint present |

## 5.3 Atomicity

Either the upload completes fully and the DB write succeeds, or both are rolled back. No partial success states.

- If the upload succeeds and the DB write fails: the upload file must be removed and the error reported
- If the DB write succeeds but the file store fails: the DB write must be rolled back
- Orphaned files (upload succeeded, no DB record) expire after 24 hours — enforced by scheduled cleanup job

```text
// Required — atomic upload + DB commit
begin_transaction()

try:
  file_path  = commit_upload(upload_id)
  db_record  = write_media_record(file_path, metadata)
  commit()
catch Exception as e:
  rollback()
  if file_path is not null: delete_file(file_path)
  log_error('Upload commit failed for {upload_id}: {e}')
  raise RuntimeError('Upload failed. Rolled back.')
```

---

# 6. Storage

- (M) Abstracted storage layer — application code must not reference a provider-specific SDK directly
- (P) Cloudflare R2 as primary object storage
- (P) S3-compatible endpoint as fallback
- (X) Hardcoded storage URLs in application code
- (X) User-controlled processing parameters — never trust client-supplied FFmpeg/ImageMagick arguments

---

# 7. Server-Side Processing

## 7.1 FFmpeg

- (M) FFmpeg invocations are sandboxed — no shell passthrough of user-supplied arguments
- (M) Output parameters defined server-side only
- (M) Processing jobs are async — never in the HTTP request lifecycle
- (X) User-supplied codec, bitrate, or filter arguments passed to FFmpeg

## 7.2 ImageMagick

- (M) ImageMagick invocations are sandboxed
- (M) Policy file restricts dangerous delegates (ghostscript, MVG, etc.)
- (X) Processing arbitrary user-uploaded files without type verification and sandboxing

## 7.3 Duration Limit Enforcement (Server-Side)

Server must independently verify duration after processing. Client-reported duration is advisory only.

| **FAIL** | media processed with user-supplied FFmpeg arguments |
| :---- | :---- |
| **FAIL** | synchronous media transcoding in HTTP request lifecycle |
| **FAIL** | server trusting client-reported duration without independent verification |

## 7.4 Audio Processing Pipeline

Server-side audio processing runs as an async job (per Standards Handbook §5). Every stage is deterministic and idempotent — re-running the pipeline on the same input produces byte-identical output.

| Order | Stage | Required | Notes |
| :---- | :---- | :---- | :---- |
| 1 | Container probe | (M) | Reject if container does not match declared format or duration exceeds cap. |
| 2 | Decode to PCM | (M) | 16-bit, mono, 16 kHz. Resample / downmix here, not on the client. |
| 3 | Loudness normalization | (M) | Target `-16 LUFS` integrated, true-peak ceiling `-1.5 dBTP` (EBU R128 / ITU-R BS.1770). |
| 4 | Silence trimming | (M) | Strip leading and trailing silence below `-40 dBFS`, max 2 seconds per end. Mid-clip silence is preserved. |
| 5 | High-pass filter | (M) | 80 Hz HPF to remove DC and rumble. |
| 6 | Optional noise reduction | (P) | Stationary-noise reduction only; never spectral subtraction that distorts speech. Disabled if SNR estimate > 25 dB. |
| 7 | Canonical re-encode | (M) | Opus, 24 kbps target, `voip` application, 20 ms frames, FEC + DTX on. This is the archive form. |
| 8 | Waveform peaks | (M) | Generate `peaks.json` with 1000 evenly-spaced peak samples for UI use. |
| 9 | Checksum + metadata write | (M) | SHA-256 of canonical archive; duration, bit depth, sample rate, loudness measurements written to media record. |

| **FAIL** | audio processing pipeline stage that is non-deterministic |
| :---- | :---- |
| **FAIL** | client-side loudness normalization (server is authoritative) |
| **FAIL** | noise reduction enabled on already-clean input (degrades speech) |
| **FAIL** | canonical archive written without SHA-256 |

---

# 8. Network Awareness

All upload clients must implement offline-aware behavior:

- Check `navigator.onLine` before initiating upload
- Queue uploads to IndexedDB on offline detection
- Resume queued uploads automatically on reconnect (with exponential backoff)
- Never silently drop a queued upload

```js
// Required
async function handleUpload(file) {
  if (!navigator.onLine) {
    await queueUploadToIndexedDB(file);
    showUserMessage('Upload queued. Will resume when connected.');
    return;
  }

  await startTusUpload(file);
}

// Required — resume on reconnect
window.addEventListener('online', async () => {
  const queued = await getQueuedUploads();
  for (const item of queued) {
    await startTusUpload(item);
  }
});
```

---

# 9. Observability

| Metric | Alert Condition |
| :---- | :---- |
| Upload failure rate | > 2% of uploads |
| Chunk checksum failures | Any in production |
| Orphaned files (no DB record > 1h) | > 0 — investigate immediately |
| Upload duration > expected for size | Alert — possible transcoding bottleneck |

---

Version: 2.0 | Starisian Technologies | May 2026

Applies to: All audio, video, and file upload code governed by Starisian Technologies standards.
