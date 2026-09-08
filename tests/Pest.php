<?php

/*
 | كان غياب هذا الملف يعني أن الاختبارات تعمل خارج تطبيق لارفل كلياً:
 | config() و url() و view() كلها تفشل، فلم يكن أي ادعاء في README مُختبَراً.
 */

uses(IProfile\Seo\Tests\TestCase::class)->in(__DIR__.'/Feature', __DIR__.'/Unit');
