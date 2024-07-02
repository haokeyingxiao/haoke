<?php declare(strict_types=1);

namespace Shopware\Storefront\Page\Account\Register;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
class AccountRegisterPageLoadedEvent extends PageLoadedEvent
{
    /**
     * @var AccountRegisterPage
     */
    protected $page;

    public function __construct(
        AccountRegisterPage $page,
        SalesChannelContext $salesChannelContext,
        Request $request
    ) {
        $this->page = $page;
        parent::__construct($salesChannelContext, $request);
    }

    public function getPage(): AccountRegisterPage
    {
        return $this->page;
    }
}
