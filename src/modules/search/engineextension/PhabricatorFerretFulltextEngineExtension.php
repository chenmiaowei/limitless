<?php

namespace orangins\modules\search\engineextension;

use Exception;
use orangins\lib\db\ActiveRecord;
use orangins\modules\search\constants\PhabricatorSearchDocumentFieldType;
use orangins\modules\search\constants\PhabricatorSearchRelationship;
use orangins\modules\search\ferret\PhabricatorFerretEngine;
use orangins\modules\search\ferret\PhabricatorFerretInterface;
use orangins\modules\search\index\PhabricatorFulltextEngineExtension;
use orangins\modules\search\index\PhabricatorSearchAbstractDocument;
use yii\db\Query;

/**
 * Class PhabricatorFerretFulltextEngineExtension
 * @package orangins\modules\search\engineextension
 * @author 陈妙威
 */
final class PhabricatorFerretFulltextEngineExtension
    extends PhabricatorFulltextEngineExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'ferret';


    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getExtensionName()
    {
        return pht('Ferret Fulltext Engine');
    }


    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function shouldIndexFulltextObject($object)
    {
        return ($object instanceof PhabricatorFerretInterface);
    }


    /**
     * @param PhabricatorFerretInterface|ActiveRecord $object
     * @param PhabricatorSearchAbstractDocument $document
     * @throws Exception
     * @author 陈妙威
     */
    public function indexFulltextObject(
        $object,
        PhabricatorSearchAbstractDocument $document)
    {

        $phid = $document->getPHID();
        $engine = $object->newFerretEngine();

        $is_closed = 0;
        $author_phid = null;
        $owner_phid = null;
        foreach ($document->getRelationshipData() as $relationship) {
            list($related_type, $related_phid) = $relationship;
            switch ($related_type) {
                case PhabricatorSearchRelationship::RELATIONSHIP_OPEN:
                    $is_closed = 0;
                    break;
                case PhabricatorSearchRelationship::RELATIONSHIP_CLOSED:
                    $is_closed = 1;
                    break;
                case PhabricatorSearchRelationship::RELATIONSHIP_OWNER:
                    $owner_phid = $related_phid;
                    break;
                case PhabricatorSearchRelationship::RELATIONSHIP_UNOWNED:
                    $owner_phid = null;
                    break;
                case PhabricatorSearchRelationship::RELATIONSHIP_AUTHOR:
                    $author_phid = $related_phid;
                    break;
            }
        }

        $stemmer = $engine->newStemmer();

        // Copy all of the "title" and "body" fields to create new "core" fields.
        // This allows users to search "in title or body" with the "core:" prefix.
        $document_fields = $document->getFieldData();
        $virtual_fields = array();
        foreach ($document_fields as $field) {
            $virtual_fields[] = $field;

            list($key, $raw_corpus) = $field;
            switch ($key) {
                case PhabricatorSearchDocumentFieldType::FIELD_TITLE:
                case PhabricatorSearchDocumentFieldType::FIELD_BODY:
                    $virtual_fields[] = array(
                        PhabricatorSearchDocumentFieldType::FIELD_CORE,
                        $raw_corpus,
                    );
                    break;
            }

            $virtual_fields[] = array(
                PhabricatorSearchDocumentFieldType::FIELD_ALL,
                $raw_corpus,
            );
        }

        $empty_template = array(
            'raw' => array(),
            'term' => array(),
            'normal' => array(),
        );

        $ferret_corpus_map = array();

        foreach ($virtual_fields as $field) {
            list($key, $raw_corpus) = $field;
            if (!strlen($raw_corpus)) {
                continue;
            }

            $term_corpus = $engine->newTermsCorpus($raw_corpus);

            $normal_corpus = $stemmer->stemCorpus($raw_corpus);
            $normal_corpus = $engine->newTermsCorpus($normal_corpus);

            if (!isset($ferret_corpus_map[$key])) {
                $ferret_corpus_map[$key] = $empty_template;
            }

            $ferret_corpus_map[$key]['raw'][] = $raw_corpus;
            $ferret_corpus_map[$key]['term'][] = $term_corpus;
            $ferret_corpus_map[$key]['normal'][] = $normal_corpus;
        }

        $ferret_fields = array();
        $ngrams_source = array();
        foreach ($ferret_corpus_map as $key => $fields) {
            $raw_corpus = $fields['raw'];
            $raw_corpus = implode("\n", $raw_corpus);
            if (strlen($raw_corpus)) {
                $ngrams_source[] = $raw_corpus;
            }

            $normal_corpus = $fields['normal'];
            $normal_corpus = implode("\n", $normal_corpus);
            if (strlen($normal_corpus)) {
                $ngrams_source[] = $normal_corpus;
            }

            $term_corpus = $fields['term'];
            $term_corpus = implode("\n", $term_corpus);
            if (strlen($term_corpus)) {
                $ngrams_source[] = $term_corpus;
            }

            $ferret_fields[] = array(
                'fieldKey' => $key,
                'rawCorpus' => $raw_corpus,
                'termCorpus' => $term_corpus,
                'normalCorpus' => $normal_corpus,
            );
        }
        $ngrams_source = implode("\n", $ngrams_source);

        $ngrams = $engine->getTermNgramsFromString($ngrams_source);

        $object->openTransaction();

        try {
//            $conn = $object->establishConnection('w');
            $this->deleteOldDocument($engine, $object, $document);
//            queryfx(
//                $conn,
//                'INSERT INTO %T (objectPHID, isClosed, epochCreated, epochModified,
//          authorPHID, ownerPHID) VALUES (%s, %d, %d, %d, %ns, %ns)',
//                $engine->getDocumentTableName(),
//                $object->getPHID(),
//                $is_closed,
//                $document->getDocumentCreated(),
//                $document->getDocumentModified(),
//                $author_phid,
//                $owner_phid);

            $conn = $object->getDb();
            $conn->createCommand("INSERT INTO {$engine->getDocumentTableName()} (object_phid, is_closed, epoch_created, epoch_modified, author_phid, owner_phid) VALUES (:object_phid, :is_closed, :epoch_created, :epoch_modified, :author_phid, :owner_phid)", [
                ":object_phid" => $object->getPHID(),
                ":is_closed" => $is_closed,
                ":epoch_created" => $document->getDocumentCreated(),
                ":epoch_modified" => $document->getDocumentModified(),
                ":author_phid" => $author_phid,
                ":owner_phid" => $owner_phid
            ])->execute();

            $document_id = $conn->getLastInsertID();

            foreach ($ferret_fields as $ferret_field) {
//                queryfx(
//                    $conn,
//                    'INSERT INTO %T (documentID, fieldKey, rawCorpus, termCorpus,
//            normalCorpus) VALUES (%d, %s, %s, %s, %s)',
//                    $engine->getFieldTableName(),
//                    $document_id,
//                    $ferret_field['fieldKey'],
//                    $ferret_field['rawCorpus'],
//                    $ferret_field['termCorpus'],
//                    $ferret_field['normalCorpus']);

                $conn->createCommand("INSERT INTO {$engine->getFieldTableName()} (document_id, field_key, raw_corpus, term_corpus, normal_corpus) VALUES (:document_id, :field_key, :raw_corpus, :term_corpus, :normal_corpus)", [
                    ":document_id" => $document_id,
                    ":field_key" => $ferret_field['fieldKey'],
                    ":raw_corpus" => $ferret_field['rawCorpus'],
                    ":term_corpus" => $ferret_field['termCorpus'],
                    ":normal_corpus" => $ferret_field['normalCorpus']
                ])->execute();
            }

            if ($ngrams) {
//                $common = queryfx_all(
//                    $conn,
//                    'SELECT ngram FROM %T WHERE ngram IN (%Ls)',
//                    $engine->getCommonNgramsTableName(),
//                    $ngrams);

                $common = (new Query())
                    ->from($engine->getCommonNgramsTableName())
                    ->andWhere(['IN', 'ngram', $ngrams])
                    ->all();
                $common = ipull($common, 'ngram', 'ngram');

                foreach ($ngrams as $key => $ngram) {
                    if (isset($common[$ngram])) {
                        unset($ngrams[$key]);
                        continue;
                    }

                    // NOTE: MySQL discards trailing whitespace in CHAR(X) columns.
                    $trim_ngram = rtrim($ngram, ' ');
                    if (isset($common[$ngram])) {
                        unset($ngrams[$key]);
                        continue;
                    }
                }
            }

            if ($ngrams) {
                $sql = array();
                foreach ($ngrams as $ngram) {
                    $sql[] = [
                        "document_id" => $document_id,
                        "ngram" => $ngram,
                    ];
//                    qsprintf(
//                        $conn,
//                        '(%d, %s)',
//                        $document_id,
//                        $ngram);
                }

                foreach (ActiveRecord::chunkSQL($sql) as $chunk) {
                    $conn->createCommand()->batchInsert($engine->getNgramsTableName(), [
                        "document_id",
                        "ngram",
                    ], $chunk)->execute();
//                    queryfx(
//                        $conn,
//                        'INSERT INTO %T (documentID, ngram) VALUES %LQ',
//                        $engine->getNgramsTableName(),
//                        $chunk);
                }
            }
        } catch (Exception $ex) {
            $object->killTransaction();
            throw $ex;
        }

        $object->saveTransaction();
    }


    /**
     * @param PhabricatorFerretEngine $engine
     * @param ActiveRecord $object
     * @param PhabricatorSearchAbstractDocument $document
     * @throws \yii\db\Exception
     * @author 陈妙威
     */
    private function deleteOldDocument(
        PhabricatorFerretEngine $engine,
        $object,
        PhabricatorSearchAbstractDocument $document)
    {

        $conn = $object->getDb();
//        $old_document = queryfx_one(
//            $conn,
//            'SELECT * FROM %T WHERE objectPHID = %s',
//            $engine->getDocumentTableName(),
//            $object->getPHID());

        $old_document = (new Query())
            ->from($engine->getDocumentTableName())
            ->andWhere([
                'object_phid' => $object->getPHID()
            ])
            ->one();
        if (!$old_document) {
            return;
        }

        $old_id = $old_document['id'];

//        queryfx(
//            $conn,
//            'DELETE FROM %T WHERE id = %d',
//            $engine->getDocumentTableName(),
//            $old_id);

        $conn->createCommand("DELETE FROM {$engine->getDocumentTableName()} WHERE id = :id", [
            ":id" => $old_id
        ])->execute();


//        queryfx(
//            $conn,
//            'DELETE FROM %T WHERE documentID = %d',
//            $engine->getFieldTableName(),
//            $old_id);

        $conn->createCommand("DELETE FROM {$engine->getFieldTableName()} WHERE id = :id", [
            ":id" => $old_id
        ])->execute();

//        queryfx(
//            $conn,
//            'DELETE FROM %T WHERE documentID = %d',
//            $engine->getNgramsTableName(),
//            $old_id);

        $conn->createCommand("DELETE FROM {$engine->getNgramsTableName()} WHERE id = :id", [
            ":id" => $old_id
        ])->execute();
    }
}
