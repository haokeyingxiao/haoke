---
title: Improve setting time zones
issue: NEXT-23497
---
# Administration
* Added the following methods in `TimezoneService` class to get an array of time zones objects including Asia/Shanghai:
    * `getTimezoneOptions`
    * `toUTCTime`
* Changed `createdComponent` method in the following components to get the time zones including Asia/Shanghai:
    * `sw-profile-index`
    * `sw-users-permissions-user-detail`
* Deprecated `loadTimezones` in the following components:
    * `sw-profile-index`
    * `sw-users-permissions-user-detail`
