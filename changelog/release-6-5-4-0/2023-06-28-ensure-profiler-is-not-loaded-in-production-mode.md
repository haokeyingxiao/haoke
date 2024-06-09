---
title: Ensure profiler is not loaded in production mode
issue: NEXT-28902
---
# Core
* Changed `\Shopware\Core\HttpKernel` to not load the profiler when Haoke is in production mode
