import template from './sw-flow-create-rule-modal.html.twig';
import './sw-flow-create-rule-modal.scss';

const { Component, Mixin, Context } = Shopware;
const { mapPropertyErrors } = Component.getComponentHelper();

Component.register('sw-flow-create-rule-modal', {
    template,

    inject: [
        'acl',
        'repositoryFactory',
        'ruleConditionDataProviderService',
    ],

    mixins: [
        Mixin.getByName('placeholder'),
        Mixin.getByName('notification'),
    ],

    props: {
        sequence: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            isLoading: false,
            active: 'rule',
            rule: {
                tags: [],
            },
            conditions: null,
        };
    },

    computed: {
        ruleRepository() {
            return this.repositoryFactory.create('rule');
        },

        conditionRepository() {
            if (!this.rule) {
                return null;
            }

            return this.repositoryFactory.create(
                this.rule.conditions.entity,
                this.rule.conditions.source,
            );
        },

        availableModuleTypes() {
            const moduleTypes = this.ruleConditionDataProviderService.getModuleTypes(moduleType => moduleType);

            return moduleTypes.map(moduleType => {
                return {
                    value: moduleType.id,
                    label: this.$tc(`${moduleType.name}`),
                };
            });
        },

        ...mapPropertyErrors('rule', ['name', 'priority']),
    },

    created() {
        this.createNewRule();
    },

    methods: {
        createNewRule() {
            this.rule = this.ruleRepository.create(Context.api);
            this.conditions = this.rule.conditions;
        },

        onConditionsChanged({ conditions }) {
            this.conditionTree = conditions;
        },

        getRuleDetail() {
            if (!this.sequence?.ruleId) {
                return null;
            }

            return this.ruleRepository.get(this.sequence.ruleId)
                .then((rule) => {
                    this.sequence.rule = rule;
                })
                .catch(() => {
                    this.sequence.rule = null;
                })
                .finally(() => {
                    this.$emit('process-finish');
                    this.onClose();
                });
        },

        onSaveRule() {
            this.isLoading = true;
            this.rule.conditions = this.conditionTree;
            return this.ruleRepository.save(this.rule, Context.api)
                .then(() => {
                    this.createNotificationSuccess({
                        message: this.$tc('sw-settings-rule.detail.messageSaveSuccess', 0, { name: this.rule.name }),
                    });
                    Shopware.State.dispatch('error/resetApiErrors');
                    this.sequence.ruleId = this.rule.id;
                    this.getRuleDetail();
                })
                .catch(() => {
                    this.createNotificationError({
                        message: this.$tc('sw-settings-rule.detail.messageSaveError', 0, { name: this.rule.name }),
                    });
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },

        onClose() {
            this.$emit('modal-close');
        },
    },
});
