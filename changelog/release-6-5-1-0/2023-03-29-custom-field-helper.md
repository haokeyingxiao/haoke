---
title: Custom field helper
issue: NEXT-25977
author: Oliver Skroblin
author_email: o.skroblin@haokeyingxiao.com
---
# Core
* Added helper functions to access and change custom fields in entities:
  * `\Shopware\Core\Framework\DataAbstractionLayer\EntityCollection::setCustomFields`
  * `\Shopware\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait::changeCustomFields`
  * `\Shopware\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait::getCustomFieldValues`
