<?php

namespace orangins\modules\search\ferret;

use orangins\lib\OranginsObject;
use orangins\lib\helpers\OranginsUtf8;
use orangins\modules\search\constants\PhabricatorSearchDocumentFieldType;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use PhutilSearchQueryCompilerSyntaxException;
use PhutilSearchStemmer;

/**
 * Class PhabricatorFerretEngine
 * @package orangins\modules\search\engineextension
 * @author 陈妙威
 */
abstract class PhabricatorFerretEngine extends OranginsObject
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getApplicationName();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getScopeName();

    /**
     * @return PhabricatorApplicationSearchEngine
     * @author 陈妙威
     */
    abstract public function newSearchEngine();

    /**
     * @return string
     * @author 陈妙威
     */
    public function getDefaultFunctionKey()
    {
        return 'all';
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getObjectTypeRelevance()
    {
        return 1000;
    }

    /**
     * @param $function
     * @return mixed
     * @author 陈妙威
     * @throws PhutilSearchQueryCompilerSyntaxException
     * @throws \Exception
     */
    public function getFieldForFunction($function)
    {
        $function = OranginsUtf8::phutil_utf8_strtolower($function);

        $map = $this->getFunctionMap();
        if (!isset($map[$function])) {
            throw new PhutilSearchQueryCompilerSyntaxException(
                \Yii::t('app',
                    'Unknown search function "{0}". Supported functions are: {1}.',
                    [
                        $function,
                        implode(', ', array_keys($map))
                    ]));
        }

        return $map[$function]['field'];
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getAllFunctionFields()
    {
        $map = $this->getFunctionMap();

        $fields = array();
        foreach ($map as $key => $spec) {
            $fields[] = $spec['field'];
        }

        return $fields;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getFunctionMap()
    {
        return array(
            'all' => array(
                'field' => PhabricatorSearchDocumentFieldType::FIELD_ALL,
                'aliases' => array(
                    'any',
                ),
            ),
            'title' => array(
                'field' => PhabricatorSearchDocumentFieldType::FIELD_TITLE,
                'aliases' => array(),
            ),
            'body' => array(
                'field' => PhabricatorSearchDocumentFieldType::FIELD_BODY,
                'aliases' => array(),
            ),
            'core' => array(
                'field' => PhabricatorSearchDocumentFieldType::FIELD_CORE,
                'aliases' => array(),
            ),
            'comment' => array(
                'field' => PhabricatorSearchDocumentFieldType::FIELD_COMMENT,
                'aliases' => array(
                    'comments',
                ),
            ),
        );
    }

    /**
     * @return PhutilSearchStemmer
     * @author 陈妙威
     */
    public function newStemmer()
    {
        return new PhutilSearchStemmer();
    }

    /**
     * @param $value
     * @return array[]|false|string|string[]
     * @author 陈妙威
     */
    public function tokenizeString($value)
    {
        $value = trim($value, ' ');
        $value = preg_split('/\s+/u', $value);
        return $value;
    }

    /**
     * @param $string
     * @return array
     * @author 陈妙威
     * @throws \Exception
     */
    public function getTermNgramsFromString($string)
    {
        return $this->getNgramsFromString($string, true);
    }

    /**
     * @param $string
     * @return array
     * @author 陈妙威
     * @throws \Exception
     */
    public function getSubstringNgramsFromString($string)
    {
        return $this->getNgramsFromString($string, false);
    }

    /**
     * @param $value
     * @param $as_term
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    private function getNgramsFromString($value, $as_term)
    {
        $value = OranginsUtf8::phutil_utf8_strtolower($value);
        $tokens = $this->tokenizeString($value);

        // First, extract unique tokens from the string. This reduces the number
        // of `phutil_utf8v()` calls we need to make if we are indexing a large
        // corpus with redundant terms.
        $unique_tokens = array();
        foreach ($tokens as $token) {
            if ($as_term) {
                $token = ' ' . $token . ' ';
            }

            $unique_tokens[$token] = true;
        }

        $ngrams = array();
        foreach ($unique_tokens as $token => $ignored) {
            $token_v = OranginsUtf8::phutil_utf8v($token);
            $length = count($token_v);

            // NOTE: We're being somewhat clever here to micro-optimize performance,
            // especially for very long strings. See PHI87.

            $token_l = array();
            for ($ii = 0; $ii < $length; $ii++) {
                $token_l[$ii] = strlen($token_v[$ii]);
            }

            $ngram_count = $length - 2;
            $cursor = 0;
            for ($ii = 0; $ii < $ngram_count; $ii++) {
                $ngram_l = $token_l[$ii] + $token_l[$ii + 1] + $token_l[$ii + 2];

                $ngram = substr($token, $cursor, $ngram_l);
                $ngrams[$ngram] = $ngram;

                $cursor += $token_l[$ii];
            }
        }

        ksort($ngrams);

        return array_keys($ngrams);
    }

    /**
     * @param $raw_corpus
     * @return null|string|string[]
     * @author 陈妙威
     */
    public function newTermsCorpus($raw_corpus)
    {
        $term_corpus = strtr(
            $raw_corpus,
            array(
                '!' => ' ',
                '"' => ' ',
                '#' => ' ',
                '$' => ' ',
                '%' => ' ',
                '&' => ' ',
                '(' => ' ',
                ')' => ' ',
                '*' => ' ',
                '+' => ' ',
                ',' => ' ',
                '-' => ' ',
                '/' => ' ',
                ':' => ' ',
                ';' => ' ',
                '<' => ' ',
                '=' => ' ',
                '>' => ' ',
                '?' => ' ',
                '@' => ' ',
                '[' => ' ',
                '\\' => ' ',
                ']' => ' ',
                '^' => ' ',
                '`' => ' ',
                '{' => ' ',
                '|' => ' ',
                '}' => ' ',
                '~' => ' ',
                '.' => ' ',
                '_' => ' ',
                "\n" => ' ',
                "\r" => ' ',
                "\t" => ' ',
            ));

        // NOTE: Single quotes divide terms only if they're at a word boundary.
        // In contractions, like "whom'st've", the entire word is a single term.
        $term_corpus = preg_replace('/(^| )[\']+/', ' ', $term_corpus);
        $term_corpus = preg_replace('/[\']+( |$)/', ' ', $term_corpus);

        $term_corpus = preg_replace('/\s+/u', ' ', $term_corpus);
        $term_corpus = trim($term_corpus, ' ');

        if (strlen($term_corpus)) {
            $term_corpus = ' ' . $term_corpus . ' ';
        }

        return $term_corpus;
    }

    /* -(  Schema  )------------------------------------------------------------- */

    /**
     * @return string
     * @author 陈妙威
     */
    public function getDocumentTableName()
    {
        $application = $this->getApplicationName();
        $scope = $this->getScopeName();

        return "{$application}_{$scope}_fdocument";
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getDocumentSchemaColumns()
    {
        return array(
            'id' => 'auto',
            'objectPHID' => 'phid',
            'isClosed' => 'bool',
            'authorPHID' => 'phid?',
            'ownerPHID' => 'phid?',
            'epochCreated' => 'epoch',
            'epochModified' => 'epoch',
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getDocumentSchemaKeys()
    {
        return array(
            'PRIMARY' => array(
                'columns' => array('id'),
                'unique' => true,
            ),
            'key_object' => array(
                'columns' => array('objectPHID'),
                'unique' => true,
            ),
            'key_author' => array(
                'columns' => array('authorPHID'),
            ),
            'key_owner' => array(
                'columns' => array('ownerPHID'),
            ),
            'key_created' => array(
                'columns' => array('epochCreated'),
            ),
            'key_modified' => array(
                'columns' => array('epochModified'),
            ),
        );
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getFieldTableName()
    {
        $application = $this->getApplicationName();
        $scope = $this->getScopeName();

        return "{$application}_{$scope}_ffield";
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getFieldSchemaColumns()
    {
        return array(
            'id' => 'auto',
            'documentID' => 'uint32',
            'fieldKey' => 'text4',
            'rawCorpus' => 'sort',
            'termCorpus' => 'sort',
            'normalCorpus' => 'sort',
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getFieldSchemaKeys()
    {
        return array(
            'PRIMARY' => array(
                'columns' => array('id'),
                'unique' => true,
            ),
            'key_documentfield' => array(
                'columns' => array('documentID', 'fieldKey'),
                'unique' => true,
            ),
        );
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getNgramsTableName()
    {
        $application = $this->getApplicationName();
        $scope = $this->getScopeName();

        return "{$application}_{$scope}_fngrams";
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getNgramsSchemaColumns()
    {
        return array(
            'id' => 'auto',
            'documentID' => 'uint32',
            'ngram' => 'char3',
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getNgramsSchemaKeys()
    {
        return array(
            'PRIMARY' => array(
                'columns' => array('id'),
                'unique' => true,
            ),
            'key_ngram' => array(
                'columns' => array('ngram', 'documentID'),
            ),
            'key_object' => array(
                'columns' => array('documentID'),
            ),
        );
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getCommonNgramsTableName()
    {
        $application = $this->getApplicationName();
        $scope = $this->getScopeName();

        return "{$application}_{$scope}_fngrams_common";
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getCommonNgramsSchemaColumns()
    {
        return array(
            'id' => 'auto',
            'ngram' => 'char3',
            'needsCollection' => 'bool',
        );
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getCommonNgramsSchemaKeys()
    {
        return array(
            'PRIMARY' => array(
                'columns' => array('id'),
                'unique' => true,
            ),
            'key_ngram' => array(
                'columns' => array('ngram'),
                'unique' => true,
            ),
            'key_collect' => array(
                'columns' => array('needsCollection'),
            ),
        );
    }

}
