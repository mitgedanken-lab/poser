 <?php

    declare(strict_types=1);

    /**
     * @package openpsa.poser
     * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
     * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
     * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
     */

    namespace openpsa\poser;

    use Composer\Script\Event;

    /**
     * 
     * @todo Write documentation
     */
    final class Installer
    {
        /**
         * @param Event $event The event we're called from
         */
        public static function setup(Event $event): void
        {
            $linker = new linker($event->getIO(), \dirname(__DIR__) . '/bin');
            $linker->link('poser');
        }
    }
