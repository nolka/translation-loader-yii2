<?php

namespace TranslationLoader\Yii2\Reader;

use common\helpers\StringHelper;
use Generator;
use TranslationLoader\Data\DataRow;
use TranslationLoader\Reader\BaseReader;
use TranslationLoader\Reader\TranslationReaderInterface;
use TranslationLoader\Yii2\Constants;
use yii\db\Expression;
use yii\db\Query;

/**
 * Yii2 database translations reader
 * @package TranslationLoader\Yii2\Reader
 */
class DbReader extends BaseReader implements TranslationReaderInterface
{
    /** @var string Table name with source strings */
    public $translateSourceName = 'translate_source';
    /** @var string Table name with translations */
    public $translateTranslationName = 'translate_translation';

    public function read(): Generator
    {
        $query = (new Query())->select(['ts.*', 'tt.*'])
            ->from("{$this->translateSourceName} ts")
            ->leftJoin("{$this->translateTranslationName} tt", ['ts.id' => new Expression('tt.source_id')]);

        foreach ($query->each() as $idx => $translation) {
            if (StringHelper::startsWith($translation['message'], Constants::UNUSED_MESSAGE_PREFIX)) {
                continue;
            }
            $dataRow = new DataRow();
            $dataRow->sourceLangCode = static::CELL_SOURCE_NAME;
            if ($translation['category'] == 'app') {
                $dataRow->sourceValue = $translation['message'];
            } else {
                $dataRow->sourceValue = Constants::LITERAL_CODE . $translation['message'];
            }
            $dataRow->destLangCode = $translation['language_id'];
            $dataRow->destValue = $translation['translation'];

            if (empty($translation['language_id']) && empty($translation['translation'])) {
                $dataRow->destLangCode = Constants::DEFAULT_LANG;
                $dataRow->destValue = "<Has no translations>";
            }
            yield $dataRow;
        }
    }
}
