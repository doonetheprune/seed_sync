<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 28/03/2015
 * Time: 17:19
 */

namespace SeedSync;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SeedSyncCommandError extends \Exception{};

class SeedSyncCommand extends Command
{
    protected $db;

    protected $supportedActions = array('download','check','calendar','pause','resume','pidKill');
    protected function configure()
    {
        $this
            ->setName('action')
            ->setDescription('Decide what you want SeedSync todo')
            ->addArgument(
                'action',
                InputArgument::REQUIRED,
                'Supply an action download|check|calendar'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $action = $input->getArgument('action');

        $this->db = DbConn::getInstance()->get();

        try{
            $hosts = $this->getHosts();

            $this->isActionValid($action);

            $class = $this->getCommandClass($action,$output);

            if($class->isModeDependant() === true && $this->shouldRun($class) === false){
                $output->writeln('<error>Command is mode dependant, mode is either OFF or mode is CALENDAR but there are not active events</error>');
            }

            foreach($hosts as $host){
                $class->execute($host);
            }
        }catch (SeedSyncCommandError $error){
            $output->writeln('<error>'.$error->getMessage().'</error>');
        }
    }

    private function getHosts()
    {
        $hosts = Host::getAllHosts($this->db);

        shuffle($hosts);

        //no hosts
        if(is_array($hosts) === false){
            throw new SeedSyncCommandError('Cannot load any hosts');
        }else{
            return $hosts;
        }
    }

    private function isActionValid($action)
    {
        if(in_array($action,$this->supportedActions) === false){
            throw new SeedSyncCommandError($action.' is not a valid action');
        }
    }

    /**
     * @param $action
     * @param OutputInterface $output
     * @return \SeedSync\Commands\CommandInterface
     * @throws SeedSyncCommandError
     */
    private function getCommandClass($action,OutputInterface $output)
    {
        $classPath = '\SeedSync\Commands\\'.ucfirst($action);

        if(class_exists($classPath) === false){
            throw new SeedSyncCommandError('Class '.$classPath.' does not exist',500);
        }

        if(in_array('SeedSync\Commands\CommandInterface',class_implements($classPath)) === false){
            throw new SeedSyncCommandError('Class does not implement CommandInterface',500);
        }

        return new $classPath($this->db,$output);
    }

    /**
     * @param \SeedSync\Commands\CommandInterface $class
     * @return bool
     */
    private function shouldRun($class)
    {
        $config = new Config();
        $mode = $config->getMode();

        if($mode == Config::MODE_ON){
            return true;
        }else if ($mode === Config::MODE_CALENDAR){
            $scheduler = new Scheduler();
            $scheduler->setCalendar(file_get_contents(__DIR__.$config->getCalendarPath()));

            $events = $scheduler->getEventType($class->getCalendarEventType());

            $numberOfEvents = count($events);

            if($numberOfEvents == 0){
                return false;
            }

            foreach($events as $event){
                $class->setCalendarEvent($event);
                return true;
            }
        }else{
            return false;
        }
    }
}