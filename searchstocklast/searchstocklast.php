<?php
/**
 * Search Stock Last
 *
 * @author Marcin Bręczewski <https://breczek-koduje.pl>
 * @license MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Searchstocklast extends Module
{
    public function __construct()
    {
        $this->name = 'searchstocklast';
        $this->tab = 'search_filter';
        $this->version = '1.0.0';
        $this->author = 'Marcin Bręczewski';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans(
            'Search: unavailable products last',
            [],
            'Modules.Searchstocklast.Admin'
        );
        $this->description = $this->trans(
            'Keeps unavailable products after purchasable products in faceted search results.',
            [],
            'Modules.Searchstocklast.Admin'
        );
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => '9.99.99',
        ];
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('productSearchProvider')) {
            return false;
        }

        // PrestaShop selects the first provider returned by this hook. Our provider
        // delegates to ps_facetedsearch and must therefore be considered first.
        $hookId = (int) Hook::getIdByName('productSearchProvider');

        if ($hookId <= 0) {
            return false;
        }

        if ((int) $this->getPosition($hookId) <= 1) {
            return true;
        }

        return $this->updatePosition($hookId, 0, 1);
    }

    /**
     * Return a patched faceted-search provider for storefront search queries only.
     * All other listings continue through the normal provider chain.
     *
     * @param array $params
     *
     * @return object|null
     */
    public function hookProductSearchProvider(array $params)
    {
        if (empty($params['query']) || !is_object($params['query'])) {
            return null;
        }

        $query = $params['query'];
        if (!method_exists($query, 'getQueryType') || $query->getQueryType() !== 'search') {
            return null;
        }

        if (!$this->isFacetedSearchEnabled()) {
            return null;
        }

        $facetedSearchModule = Module::getInstanceByName('ps_facetedsearch');
        if (!$facetedSearchModule || !method_exists($facetedSearchModule, 'getDatabase')) {
            return null;
        }

        if (!$this->loadIntegrationClasses()) {
            return null;
        }

        // If upstream already routes search relevance through computeShowLast(),
        // this compatibility module is no longer needed.
        if ($this->hasUpstreamSearchFix()) {
            return null;
        }

        try {
            if (method_exists($facetedSearchModule, 'isControllerSupported')
                && !$facetedSearchModule->isControllerSupported('search')
            ) {
                return null;
            }

            $provider = new \PrestaShop\Module\FacetedSearch\Filters\Provider(
                $facetedSearchModule->getDatabase()
            );

            // Match ps_facetedsearch behavior: it only owns search pages for which
            // a faceted-search template has been configured.
            if (empty($provider->getFiltersForQuery($query, (int) $this->context->shop->id))) {
                return null;
            }

            $urlSerializer = new \PrestaShop\Module\FacetedSearch\URLSerializer();
            $dataAccessor = new \PrestaShop\Module\FacetedSearch\Filters\DataAccessor(
                $facetedSearchModule->getDatabase()
            );

            return new \PrestaShop\Module\FacetedSearch\Product\SearchProvider(
                $facetedSearchModule,
                new \PrestaShop\Module\FacetedSearch\Filters\Converter(
                    $facetedSearchModule->getContext(),
                    $facetedSearchModule->getDatabase(),
                    $urlSerializer,
                    $dataAccessor,
                    $provider
                ),
                $urlSerializer,
                $dataAccessor,
                new \SearchStockLast\Product\SearchFactory(),
                $provider
            );
        } catch (Throwable $exception) {
            PrestaShopLogger::addLog(
                sprintf('searchstocklast: integration skipped: %s', $exception->getMessage()),
                2
            );

            return null;
        }
    }

    public function getContent()
    {
        if (!$this->isFacetedSearchEnabled()) {
            return $this->displayWarning($this->trans(
                'Faceted Search (ps_facetedsearch) must be installed and enabled. This module currently has no effect.',
                [],
                'Modules.Searchstocklast.Admin'
            ));
        }

        Module::getInstanceByName('ps_facetedsearch');
        if (!$this->loadIntegrationClasses()) {
            return $this->displayWarning($this->trans(
                'The installed Faceted Search version is not compatible. Original search behavior is unchanged.',
                [],
                'Modules.Searchstocklast.Admin'
            ));
        }

        $facetedSearchModule = Module::getInstanceByName('ps_facetedsearch');
        $securityWarning = '';
        if ($facetedSearchModule
            && version_compare((string) $facetedSearchModule->version, '4.0.4', '<')
        ) {
            $securityWarning = $this->displayWarning($this->trans(
                'Update Faceted Search to version 4.0.4 or newer before using the shop in production. '
                . 'Older releases contain a publicly disclosed security vulnerability.',
                [],
                'Modules.Searchstocklast.Admin'
            ));
        }

        if ($this->hasUpstreamSearchFix()) {
            return $securityWarning . $this->displayInformation($this->trans(
                'This Faceted Search version already contains the search ordering fix. '
                . 'You can disable and uninstall this module.',
                [],
                'Modules.Searchstocklast.Admin'
            ));
        }

        if (!$this->isBeforeFacetedSearchOnProviderHook()) {
            return $securityWarning . $this->displayWarning($this->trans(
                'Move this module before Faceted Search on the productSearchProvider hook. '
                . 'Until then, the correction may not run.',
                [],
                'Modules.Searchstocklast.Admin'
            ));
        }

        return $securityWarning . $this->displayConfirmation($this->trans(
            'The compatibility correction is active for faceted storefront search results.',
            [],
            'Modules.Searchstocklast.Admin'
        ));
    }

    private function isFacetedSearchEnabled()
    {
        return Module::isInstalled('ps_facetedsearch') && Module::isEnabled('ps_facetedsearch');
    }

    private function loadIntegrationClasses()
    {
        $requiredClasses = [
            'PrestaShop\\Module\\FacetedSearch\\Adapter\\MySQL',
            'PrestaShop\\Module\\FacetedSearch\\Product\\Search',
            'PrestaShop\\Module\\FacetedSearch\\Product\\SearchFactory',
            'PrestaShop\\Module\\FacetedSearch\\Product\\SearchProvider',
            'PrestaShop\\Module\\FacetedSearch\\Filters\\Provider',
            'PrestaShop\\Module\\FacetedSearch\\Filters\\Converter',
            'PrestaShop\\Module\\FacetedSearch\\Filters\\DataAccessor',
            'PrestaShop\\Module\\FacetedSearch\\URLSerializer',
        ];

        foreach ($requiredClasses as $className) {
            if (!class_exists($className)) {
                return false;
            }
        }

        require_once __DIR__ . '/src/Adapter/SearchStockLastMySQL.php';
        require_once __DIR__ . '/src/Product/Search.php';
        require_once __DIR__ . '/src/Product/SearchFactory.php';

        return true;
    }

    private function hasUpstreamSearchFix()
    {
        try {
            $method = new ReflectionMethod(
                'PrestaShop\\Module\\FacetedSearch\\Adapter\\MySQL',
                'computeOrderByField'
            );
            $fileName = $method->getFileName();
            if (!$fileName || !is_readable($fileName)) {
                return false;
            }

            $lines = file($fileName);
            if (!is_array($lines)) {
                return false;
            }

            $source = implode('', array_slice(
                $lines,
                $method->getStartLine() - 1,
                $method->getEndLine() - $method->getStartLine() + 1
            ));

            return (bool) preg_match(
                '/return\s+\$this->computeShowLast\s*\(\s*\$orderField\s*,\s*\$filterToTableMapping\s*\)/',
                $source
            );
        } catch (Throwable $exception) {
            return false;
        }
    }

    private function isBeforeFacetedSearchOnProviderHook()
    {
        $hookId = (int) Hook::getIdByName('productSearchProvider');
        if ($hookId <= 0) {
            return false;
        }

        $modules = Hook::getModulesFromHook($hookId);
        $ownPosition = null;
        $facetedPosition = null;

        foreach ($modules as $module) {
            $position = isset($module['m.position'])
                ? (int) $module['m.position']
                : (isset($module['position']) ? (int) $module['position'] : null);

            if ($module['name'] === $this->name) {
                $ownPosition = $position;
            } elseif ($module['name'] === 'ps_facetedsearch') {
                $facetedPosition = $position;
            }
        }

        return $ownPosition !== null
            && ($facetedPosition === null || $ownPosition < $facetedPosition);
    }
}
