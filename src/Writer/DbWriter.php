<?php

namespace TranslationLoader\Yii2\Writer;

use TranslationLoader\Data\DataRow;
use TranslationLoader\Writer\BaseWriter;
use TranslationLoader\Writer\TranslationWriterInterface;
use TranslationLoader\Yii2\Constants;
use Yii;
use yii\db\Query;
use yii\helpers\StringHelper;

/**
 * Translations writer for Yii2
 * @package TranslationLoader\Yii2\Writer
 */
class DbWriter extends BaseWriter implements TranslationWriterInterface
{
    /**
     * Phrases index to increase translation writings
     * @var array
     */
    protected $phraseIdx = [];

    /**
     * @inheritDoc
     */
    public function write(DataRow $dataRow): bool
    {
        $sourceIdx = $this->getSourcePhraseIndex($dataRow->sourceValue);
        if (empty($dataRow->destValue)) {
            return false;
        }

        $this->upsertTranslation($sourceIdx, $dataRow->destLangCode, $dataRow->destValue);

        return true;
    }

    /**
     * Returns source phrase id from database
     * @param string $phrase
     * @return int
     */
    public function getSourcePhraseIndex(string $phrase): int
    {
        $localIdx = array_search($phrase, $this->phraseIdx);
        if ($localIdx === false) {
            $idx = $this->searchPhraseInDatabase($phrase);
            if (!empty($idx)) {
                $this->addPhraseToIndex($phrase, $idx);
            } else {
                $this->createSourceMessage($phrase);
            }

            return $this->getSourcePhraseIndex($phrase);
        }
        return $localIdx;
    }

    /**
     * Search phrase in database first
     * @param string $phrase
     * @return int|null
     */
    protected function searchPhraseInDatabase(string $phrase): ?int
    {
        $query = (new Query())->from('translate_source')
            ->select('id')
            ->where(['message' => $this->parseSourcePhrase($phrase)])
            ->orderBy('id')
            ->limit(1);

        if ($this->isLiteral($phrase)) {
            $query->andWhere(['category' => 'app.literal']);
        } else {
            $query->andWhere(['category' => 'app']);
        }

        return $query->scalar() ?: null;
    }

    /**
     * Setter method
     * @param string $phrase
     * @param int $idx
     */
    protected function addPhraseToIndex(string $phrase, int $idx)
    {
        $this->phraseIdx[$idx] = $phrase;
    }

    /**
     * Parses phrase. If $phrase is literal, this method will remove literal prefix which is defined in Constants::LITERAL_CODE
     * @param string $phrase
     * @return string
     */
    protected function parseSourcePhrase(string $phrase): string
    {
        if (!$this->isLiteral($phrase)) {
            return $phrase;
        }
        return StringHelper::byteSubstr($phrase, mb_strlen(Constants::LITERAL_CODE));
    }

    /**
     * Checks if phrase is literal
     * @param string $phrase
     * @return bool
     */
    protected function isLiteral(string $phrase): bool
    {
        return StringHelper::startsWith($phrase, Constants::LITERAL_CODE);
    }

    /**
     * Store source message in database
     * @param string $phrase
     * @return bool
     * @throws \yii\db\Exception
     */
    protected function createSourceMessage(string $phrase): bool
    {
        return (bool)Yii::$app
            ->db
            ->createCommand()
            ->insert('translate_source', [
                'category' => $this->isLiteral($phrase) ? 'app.literal' : 'app',
                'message' => $this->parseSourcePhrase($phrase)
            ])
            ->execute();
    }

    /**
     * Insert or update translation to specified language code
     * @param int $sourceIdx
     * @param string $destLangCode
     * @param string $destValue
     * @return int
     * @throws \yii\db\Exception
     */
    protected function upsertTranslation(int $sourceIdx, string $destLangCode, string $destValue): int
    {
        return (bool)Yii::$app
            ->db
            ->createCommand()
            ->upsert('translate_translation',
                [
                    'source_id' => $sourceIdx,
                    'language_id' => $destLangCode,
                    'translation' => $destValue,
                ],
                [
                    'translation' => $destValue,
                ])->execute();
    }
}
