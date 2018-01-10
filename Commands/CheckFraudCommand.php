<?php

namespace Ebizmarts\SagePaySuite\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class CheckFraudCommand extends Command
{
    /** @var \Ebizmarts\SagePaySuite\Model\Cron $posOrder*/
    private $cron;
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;
    /** @var \Magento\Framework\App\State  */
    protected $appState;

    public function __construct(
        \Ebizmarts\SagePaySuite\Model\Cron $cron,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\ObjectManagerInterface $objectManager
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
        $this->setDescription('Sage Pay check fraud on transactions.');
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
