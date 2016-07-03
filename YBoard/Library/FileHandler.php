<?php
namespace YBoard\Library;

class FileHandler
{
    // Potentially unsafe to allow these to be changed from scripts. Don't do that.
    const NICE_VALUE = 19;
    const PNGCRUSH_OPTIONS = '-reduce -fix -rem allb -l 9';
    const IMAGICK_FILTER = 'triangle';

    public static function createImage(string $file, string $destination, int $maxWidth, int $maxHeight) : bool
    {
        set_time_limit(60);

        if (is_file($destination)) {
            return false;
        }

        $cmd = 'nice --adjustment=' . (int)static::NICE_VALUE . ' convert';

        // Set limits
        $cmd .= ' -limit area 512MiB -limit memory 128MiB -limit map 256MiB - limit disk 1GiB -limit time 60';

        // Input file
        $cmd .= ' ' . escapeshellarg($file);

        // Keep only the first frame
        $cmd .= '[0]';

        // Reset virtual canvas
        $cmd .= ' +repage';

        // Set filter
        $cmd .= ' -filter ' . escapeshellarg(static::IMAGICK_FILTER);

        // Resize larger than maxSizes
        $cmd .= ' -resize ' . (int)$maxWidth . 'x' . (int)$maxHeight . '\>';

        // Set quality
        $cmd .= ' -quality 80';

        // Strip color profiles, comments, etc.
        $cmd .= ' -strip';

        // Flatten
        $cmd .= ' -flatten';

        // Output file
        $cmd .= ' ' . escapeshellarg('jpg:' . $destination);

        shell_exec($cmd);

        if (filesize($destination) == 0) {
            unlink($destination);
        }

        return is_file($destination) !== false;
    }

    public static function pngCrush(string $file) : bool
    {
        $tmpFile = $file . '.tmp.png';

        $cmd = 'nice --adjustment=' . (int)static::NICE_VALUE . ' pngcrush ';
        $cmd .= static::PNGCRUSH_OPTIONS . ' ' . escapeshellarg($file) . ' ' . escapeshellarg($tmpFile);

        shell_exec($cmd);

        if (filesize($tmpFile) == 0) {
            unlink($tmpFile);
        }

        if (!is_file($tmpFile)) {
            return false;
        }

        unlink($file);
        rename($tmpFile, $file);

        return is_file($file) !== false;
    }

    public static function jheadAutorot(string $file) : bool
    {
        // Rotate jpeg by exif tag
        shell_exec('nice --adjustment=' . (int)static::NICE_VALUE . ' jhead -autorot ' . escapeshellarg($file));

        if (filesize($file) == 0) {
            unlink($file);
        }

        return is_file($file) !== false;
    }

    public static function jpegtran(string $file, bool $progressive = false) : bool
    {
        $fileSafe = escapeshellarg($file);
        $cmd = 'jpegtran -optimize';

        if ($progressive) {
            $cmd .= ' -progressive';
        }
        $cmd .= ' -maxmemory 131072 -copy none -outfile ' . $fileSafe . ' ' . $fileSafe;

        shell_exec('nice --adjustment=' . (int)static::NICE_VALUE . ' ' . $cmd);

        if (filesize($file) == 0) {
            unlink($file);
        }

        return is_file($file) !== false;
    }
}
