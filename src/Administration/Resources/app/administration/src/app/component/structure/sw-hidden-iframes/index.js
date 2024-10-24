import { MAIN_HIDDEN } from '@haokeyingxiao/meteor-admin-sdk/es/location';
import template from './sw-hidden-iframes.html.twig';

const { Component } = Shopware;

/**
 * @package admin
 *
 * @private
 */
Component.register('sw-hidden-iframes', {
    template,

    compatConfig: Shopware.compatConfig,

    computed: {
        extensions() {
            return Shopware.State.getters['extensions/privilegedExtensions'];
        },

        MAIN_HIDDEN() {
            return MAIN_HIDDEN;
        },
    },
});
