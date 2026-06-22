<?php

namespace ExamplePlugin;

// Missing declare(strict_types=1) — triggers PHP-STRICT-001
class MissingStrict {

    public function get_data(): string {
        return 'data';
    }
}
