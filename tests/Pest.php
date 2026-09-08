<?php

use IProfile\Seo\Tests\TestCase;

/*
 | كان غياب هذا الملف يعني أن الاختبارات تعمل خارج تطبيق لارفل كلياً:
 | config() و url() و view() كلها تفشل، فلم يكن أي ادعاء في README مُختبَراً.
 */

uses(TestCase::class)->in(__DIR__.'/Feature', __DIR__.'/Unit');
