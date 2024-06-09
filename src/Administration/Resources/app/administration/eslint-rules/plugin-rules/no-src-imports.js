/**
 * @package admin
 */

/* eslint-disable max-len */
module.exports = {
    create(context) {
        return {
            ImportDeclaration(node) {
                const invalidNodeSources = [];
                invalidNodeSources.push(node.source.value.startsWith('@administration/'));

                if (invalidNodeSources.includes(true)) {
                    context.report({
                        loc: node.source.loc.start,
                        message: `\
You can't use imports directly from the Haoke Core via "${node.source.value}". \
Use the global Haoke object directly instead (https://developer.haokeyingxiao.com/docs/guides/plugins/plugins/administration/the-shopware-object)`,
                    });
                }
            },
        };
    },
};
