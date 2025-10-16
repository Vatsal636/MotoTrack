<?php
/**
 * Simple Environment File Loader
 * Loads .env file and makes variables available via getenv()
 */

function loadEnv($path)
{
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments and empty lines
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }

        // Parse key=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Skip if key is empty or starts with #
            if (empty($key) || strpos($key, '#') !== false) {
                continue;
            }

            // Remove quotes if present
            if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            // Always set in environment (overwrite existing)
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    return true;
}

/**
 * Get environment variable with fallback
 * InfinityFree compatible - checks $_ENV and $_SERVER
 */
function env($key, $default = null)
{
    // Try $_ENV first (most reliable on InfinityFree)
    if (isset($_ENV[$key])) {
        $value = $_ENV[$key];
    }
    // Try $_SERVER as fallback
    elseif (isset($_SERVER[$key])) {
        $value = $_SERVER[$key];
    }
    // Try getenv as last resort
    elseif (($value = getenv($key)) !== false) {
        // getenv worked
    }
    else {
        return $default;
    }

    // Convert boolean strings
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'empty':
        case '(empty)':
            return '';
        case 'null':
        case '(null)':
            return null;
    }

    return $value;
}

// Load environment file
$envPath = __DIR__ . '/../.env';
if (!loadEnv($envPath)) {
    // .env not found, check if we're in development
    if (file_exists(__DIR__ . '/../.env.example')) {
        error_log('WARNING: .env file not found. Please copy .env.example to .env and configure it.');
    }
}
?>