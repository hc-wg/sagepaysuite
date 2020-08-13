<?php

namespace Ebizmarts\SagePaySuite\Commands;

use Ebizmarts\SagePaySuite\Model\Cron;
use Magento\Framework\App\State;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class CheckFraudCommand extends Command
{
    /** @var Cron $posOrder*/
    private $cron;
    /** @var ObjectManagerInterface */
    private $objectManager;
    /** @var State  */
    protected $appState;

    /**
     * CheckFraudCommand constructor.
     * @param Cron $cron
     * @param State $appState
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Cron $cron,
        State $appState,
        ObjectManagerInterface $objectManager
    ) {
        $this->appState = $appState;
        $this->cron         = $cron;
        $this->objectManager    = $objectManager;
        parent::__construct();
    }
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('sage-pay:fraud-check');
        $this->setDescription('Opayo check fraud on transactions.');
        parent::configure();
    }
    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode('admin');

        $output->writeln("<comment>Checking fraud...</comment>");

        $this->cron->checkFraud();

        $output->writeln("<info>Done.</info>");
    }
}
