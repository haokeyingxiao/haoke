<?php declare(strict_types=1);

namespace Shopware\Core\Migration\V6_4;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransactionCapture\OrderTransactionCaptureStates;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefund\OrderTransactionCaptureRefundStates;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Migration\Traits\StateMachineMigration;
use Shopware\Core\Migration\Traits\StateMachineMigrationTrait;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1643878976AddCaptureRefundStateMachines extends MigrationStep
{
    use StateMachineMigrationTrait;

    public function getCreationTimestamp(): int
    {
        return 1643878976;
    }

    public function update(Connection $connection): void
    {
        $this->import($this->captureStateMachine(), $connection);
        $this->import($this->captureRefundStateMachine(), $connection);
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function captureStateMachine(): StateMachineMigration
    {
        return new StateMachineMigration(
            OrderTransactionCaptureStates::STATE_MACHINE,
            'Bezahlstatus',
            'Capture state',
            '捕捉状态',
            [
                StateMachineMigration::state(
                    OrderTransactionCaptureStates::STATE_PENDING,
                    'Ausstehend',
                    'Pending',
                    '挂起'
                ),
                StateMachineMigration::state(
                    OrderTransactionCaptureStates::STATE_COMPLETED,
                    'Abgeschlossen',
                    'Complete',
                    '完成'
                ),
                StateMachineMigration::state(
                    OrderTransactionCaptureStates::STATE_FAILED,
                    'Fehlgeschlagen',
                    'Failed',
                    '失败'
                ),
            ],
            [
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_COMPLETE,
                    OrderTransactionCaptureStates::STATE_PENDING,
                    OrderTransactionCaptureStates::STATE_COMPLETED
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_FAIL,
                    OrderTransactionCaptureStates::STATE_PENDING,
                    OrderTransactionCaptureStates::STATE_FAILED,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_REOPEN,
                    OrderTransactionCaptureStates::STATE_COMPLETED,
                    OrderTransactionCaptureStates::STATE_PENDING,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_REOPEN,
                    OrderTransactionCaptureStates::STATE_FAILED,
                    OrderTransactionCaptureStates::STATE_PENDING,
                ),
            ],
            OrderTransactionCaptureStates::STATE_PENDING
        );
    }

    private function captureRefundStateMachine(): StateMachineMigration
    {
        return new StateMachineMigration(
            OrderTransactionCaptureRefundStates::STATE_MACHINE,
            'Erstattungsstatus',
            'Refund state',
            '退款状态',
            [
                StateMachineMigration::state(
                    OrderTransactionCaptureRefundStates::STATE_OPEN,
                    'Offen',
                    'Open',
                    '待处理',
                ),
                StateMachineMigration::state(
                    OrderTransactionCaptureRefundStates::STATE_IN_PROGRESS,
                    'In Bearbeitung',
                    'In progress',
                    '处理中'
                ),
                StateMachineMigration::state(
                    OrderTransactionCaptureRefundStates::STATE_COMPLETED,
                    'Abgeschlossen',
                    'Completed',
                    '完成',
                ),
                StateMachineMigration::state(
                    OrderTransactionCaptureRefundStates::STATE_FAILED,
                    'Fehlgeschlagen',
                    'Failed',
                    '失败'
                ),
                StateMachineMigration::state(
                    OrderTransactionCaptureRefundStates::STATE_CANCELLED,
                    'Abgebrochen',
                    'Cancelled',
                    '已取消'
                ),
            ],
            [
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_PROCESS,
                    OrderTransactionCaptureRefundStates::STATE_OPEN,
                    OrderTransactionCaptureRefundStates::STATE_IN_PROGRESS,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_CANCEL,
                    OrderTransactionCaptureRefundStates::STATE_OPEN,
                    OrderTransactionCaptureRefundStates::STATE_CANCELLED,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_FAIL,
                    OrderTransactionCaptureRefundStates::STATE_OPEN,
                    OrderTransactionCaptureRefundStates::STATE_FAILED,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_COMPLETE,
                    OrderTransactionCaptureRefundStates::STATE_OPEN,
                    OrderTransactionCaptureRefundStates::STATE_COMPLETED,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_CANCEL,
                    OrderTransactionCaptureRefundStates::STATE_IN_PROGRESS,
                    OrderTransactionCaptureRefundStates::STATE_CANCELLED,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_FAIL,
                    OrderTransactionCaptureRefundStates::STATE_IN_PROGRESS,
                    OrderTransactionCaptureRefundStates::STATE_FAILED,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_COMPLETE,
                    OrderTransactionCaptureRefundStates::STATE_IN_PROGRESS,
                    OrderTransactionCaptureRefundStates::STATE_COMPLETED,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_REOPEN,
                    OrderTransactionCaptureRefundStates::STATE_CANCELLED,
                    OrderTransactionCaptureRefundStates::STATE_OPEN,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_REOPEN,
                    OrderTransactionCaptureRefundStates::STATE_FAILED,
                    OrderTransactionCaptureRefundStates::STATE_OPEN,
                ),
                StateMachineMigration::transition(
                    StateMachineTransitionActions::ACTION_REOPEN,
                    OrderTransactionCaptureRefundStates::STATE_COMPLETED,
                    OrderTransactionCaptureRefundStates::STATE_OPEN,
                ),
            ],
            OrderTransactionCaptureRefundStates::STATE_OPEN
        );
    }
}
