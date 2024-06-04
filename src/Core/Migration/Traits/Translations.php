<?php declare(strict_types=1);

namespace Shopware\Core\Migration\Traits;

use Shopware\Core\Framework\Log\Package;

#[Package('core')]
class Translations
{
    /**
     * @var array
     */
    protected $german;

    /**
     * @var array
     */
    protected $english;

    /**
     * @var array
     */
    protected $chinese;

    public function __construct(
        array $german,
        array $english,
        array $chinese
    ) {
        $this->german = $german;
        $this->english = $english;
        $this->chinese = $chinese;
    }

    public function getGerman(): array
    {
        return $this->german;
    }

    public function getEnglish(): array
    {
        return $this->english;
    }

    public function getColumns(): array
    {
        return array_keys($this->english);
    }

    public function getChinese(): array
    {
        return $this->chinese;
    }
}
