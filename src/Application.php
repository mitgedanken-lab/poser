 <?php

    declare(strict_types=1);

    /**
     * @package openpsa.poser
     * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
     * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
     * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
     */

    namespace openpsa\poser;

    use Composer\Console\Application as BaseApplication;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;
    use Composer\Factory;
    use Composer\Util\Filesystem;

    /**
     * 
     * @todo Write documentation(?)
     */
    final class Application extends BaseApplication
    {
        public const DEFAULT_SHARE_DIR  = '/usr/local/share/poser';

        private array $binfiles;
        private string $share_dir;

        /**
         * @inheritDoc
         */
        public function doRun(InputInterface $input, OutputInterface $output): int
        {
            /* @todo Ignore `global`; don't throw an exception */
            if ($this->getCommandName($input) == 'global') {
                throw exception::global_command_unsupported();
            }

            $fs = new Filesystem();
            $fs->ensureDirectoryExists($this->share_dir);
            \chdir($this->share_dir);
            $output->writeln('<info>Changed current directory to ' . $this->share_dir . '</info>');
            $vendor_bin = Factory::createConfig()->get('bin-dir');
            $this->binfiles = $this->list_binfiles($vendor_bin);

            $result = parent::doRun($input, $output); // @todo Review

            if (\is_dir($vendor_bin)) {
                $new_files = $this->list_binfiles($vendor_bin);
                $added = \array_diff($new_files, $this->binfiles);
                $removed = \array_diff($this->binfiles, $new_files);

                $linker = new Linker($this->io, $vendor_bin);
                foreach ($added as $file) {
                    $linker->link($file);
                }
                foreach ($removed as $file) {
                    $linker->unlink($file);
                }
            }
            return $result;
        }

        private function list_binfiles($vendor_bin): array
        {
            $files = [];
            if (!\is_dir($vendor_bin)) {
                return $files;
            }

            $iterator = new \DirectoryIterator($vendor_bin);
            foreach ($iterator as $child) {
                if (
                    $child->getType() !== 'dir'
                    && \is_executable($child->getRealPath())
                ) {
                    $files[] = $child->getBasename();
                }
            }
            return $files;
        }
    }
