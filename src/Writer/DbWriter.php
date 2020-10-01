<?php

namespace TranslationLoader\Yii2\Writer;

use TranslationLoader\Data\DataRow;
use TranslationLoader\Writer\BaseWriter;
use TranslationLoader\Writer\TranslationWriterInterface;

class DbWriter extends BaseWriter implements TranslationWriterInterface
{

    public function write(DataRow $dataRow): bool
    {
        // TODO: Implement write() method.
    }

    public function finalize(): void
    {
        // TODO: Implement finalize() method.
    }
}
