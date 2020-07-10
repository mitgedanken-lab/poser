 <?php

    declare(strict_types=1);

    /**
     * @package openpsa.poser
     * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
     * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
     * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
     */

    namespace openpsa\poser;

    use Composer\IO\IOInterface;

    /**
     * Makes a Link
     * @todo Make it ready for PHP 7+
     * @todo Write documentation
     */
    final class Linker
    {
        public const DEFAULT_SYSTEM_BIN = '/usr/local/bin';

        private IOInterface $io;
        private string $system_bin;
        private string $vendor_bin;
        private string $readonly_behavior;

        /**
         * Constructor
         * @todo Write documentation
         */
        public function __construct(IOInterface $io, string $vendor_bin, string $system_bin = self::DEFAULT_SYSTEM_BIN)
        {
            $this->io = $io;
            $this->vendor_bin = $vendor_bin;
            $this->system_bin = $system_bin;
        }

        /**
         *
         * @todo Write documentation
         */
        public function link(string $file): void
        {
            $linkname = $this->system_bin . '/' . $file;
            $target_path = \realpath($this->vendor_bin . '/' . $file);

            if (!\file_exists($target_path)) {
                throw exception::nonexistent_target($target_path);
            }

            if (\is_link($linkname)) {
                if (!\file_exists(\realpath($linkname))) {
                    $this->io->write('Link in <info>' . $linkname . '</info> points to nonexistent path, removing');
                    @\unlink($linkname);
                } else {
                    if (
                        \realpath($linkname) !== $target_path
                        && \md5_file(\realpath($linkname)) !== \md5_file($target_path)
                    ) {
                        $this->io->write('Skipping <info>' . \basename($target_path) . '</info>: Found Link in <info>' . \dirname($linkname) . '</info> to <comment>' . \realpath($linkname) . '</comment>');
                    }
                    return;
                }
            } else if (\is_file($linkname)) {
                if (\md5_file($linkname) !== \md5_file($target_path)) {
                    $this->io->write('Skipping <info>' . \basename($target_path) . '</info>: Found existing file in <comment>' . \dirname($linkname) . '</comment>');
                }
                return;
            }

            if (!\is_writeable(\dirname($linkname))) {
                if ($this->readonly_behavior === null) {
                    $this->io->write('Directory <info>' . \dirname($linkname) . '</info> is not writeable.');
                    $reply = $this->io->ask('<question>Please choose:</question> [<comment>(S)udo</comment>, (I)gnore, (A)bort]', 'S');
                    $this->readonly_behavior = \strtolower(\trim($reply));
                }
                switch ($this->readonly_behavior) {
                    case 'a':
                        throw new exception('Aborted by user command');
                    case 'i':
                        $this->io->write('<info>Skipped linking ' . \basename($linkname) . ' to ' . \dirname($linkname) . '</info>');
                        return;
                    case '':
                    case 's':
                    default:
                        \exec('sudo ln -s ' . \escapeshellarg($target_path) . ' ' . \escapeshellarg($linkname), $output, $return);
                        if ($return !== 0) {
                            throw exception::shell_error($linkname, $output);
                        }
                        break;
                }
            } else {
                if (!@\symlink($target_path, $linkname)) {
                    throw exception::php_error($linkname);
                }
            }
            if ($this->io->isVerbose()) {
                $this->io->write('Linked <info>' . \basename($linkname) . '</info> to <comment>' . \dirname($linkname) . '</comment>');
            }
        }

        /**
         *
         * @todo Write documentation
         */
        public function unlink($file): void
        {
            $linkname = $this->system_bin . '/' . $file;
            $target_path = $this->vendor_bin . '/' . $file;

            if (\is_link($linkname)) {
                if (
                    \file_exists(\realpath($linkname))
                    && \realpath($linkname) !== $target_path
                ) {
                    if ($this->io->isVerbose()) {
                        $this->io->write('Skipping deletion of <info>' . \basename($target_path) . '</info>: Found Link in <info>' . \dirname($linkname) . '</info> to <comment>' . \realpath($linkname) . '</comment>');
                        return;
                    }
                }
                $this->io->write('Removing link <info>' . $linkname . '</info>');
                @\unlink($linkname);
            }
        }
    }
