import template from './hk-first-run-wizard-welcome.html.twig';
import './hk-first-run-wizard-welcome.scss';

const { Criteria } = Shopware.Data;
/**
 * @package checkout
 * @private
 */
export default {
    template,
    inject: [
        'repositoryFactory',
        'extensionStoreActionService',
    ],
    mixins: [
        'notification',
    ],

    data() {
        return {
            plugins: {
                base: {
                    name: 'HkagBase',
                    isInstalled: false,
                },
            },
            basePluginName: 'HkagBase',
            isInstallingPlugin: false,
            installationError: false,
            pluginError: null,
            pluginInstalledSuccessfully: {
                base: false,
            },
            latestTouchedPlugin: null,
            isLoading: false,
        };
    },
    created() {
        this.createdComponent();
    },

    computed: {
        pluginRepository() {
            return this.repositoryFactory.create('plugin');
        },

        userRepository() {
            return this.repositoryFactory.create('user');
        },
        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },
    watch: {
        isInstallingPlugin() {
            this.updateButtons();
        },
    },
    methods: {
        createdComponent() {
            this.setTitle();
            this.getInstalledPlugins();
            this.updateButtons();
        },
        setTitle() {
            this.$emit('frw-set-title', this.$tc('sw-first-run-wizard.welcome.modalTitle'));
        },
        notInstalled(pluginKey) {
            return !this.plugins[pluginKey].isInstalled;
        },
        updateButtons() {
            const disabledExtensionManagement = Shopware.State.get('context').app.config.settings.disableExtensionManagement;
            const nextRoute = disabledExtensionManagement ? 'defaults' : 'data-import';

            const buttonConfig = [
                {
                    key: 'next',
                    label: this.$tc('sw-first-run-wizard.general.buttonNext'),
                    position: 'right',
                    variant: 'primary',
                    action: `sw.first.run.wizard.index.${nextRoute}`,
                    disabled: !this.plugins.base.isInstalled,
                },
            ];

            this.$emit('buttons-update', buttonConfig);
        },
        onInstall(pluginKey) {
            const plugin = this.plugins[pluginKey];
            this.isInstallingPlugin = true;
            this.installationError = false;

            return this.extensionStoreActionService.downloadExtension(plugin.name)
                .then(() => {
                    return this.extensionStoreActionService.installExtension(plugin.name, 'plugin');
                })
                .then(() => {
                    return this.extensionStoreActionService.activateExtension(plugin.name, 'plugin');
                })
                .then(() => {
                    this.$emit('extension-activated');
                    this.isInstallingPlugin = false;
                    this.plugins[pluginKey].isInstalled = true;

                    return false;
                })
                .catch((error) => {
                    this.isInstallingPlugin = false;
                    this.installationError = true;

                    if (error.response?.data?.errors) {
                        this.pluginError = error.response.data.errors.pop();
                    }

                    return true;
                });
        },
        getInstalledPlugins() {
            const pluginNames = Object.values(this.plugins).map(plugin => plugin.name);
            const pluginCriteria = new Criteria(1, 5);

            pluginCriteria
                .addFilter(
                    Criteria.equalsAny('plugin.name', pluginNames),
                );

            this.pluginRepository.search(pluginCriteria)
                .then((result) => {
                    if (result.total < 1) {
                        return;
                    }

                    result.forEach((plugin) => {
                        if (!plugin.active || plugin.installedAt === null) {
                            return;
                        }
                        const key = this.findPluginKeyByName(plugin.name);

                        this.plugins[key].isInstalled = true;
                    });
                });
        },

        findPluginKeyByName(name) {
            const [pluginKey] = Object.entries(this.plugins).find(([key, state]) => {
                if (state.name === name) {
                    return key;
                }

                return '';
            });

            return pluginKey;
        },

    },
};
