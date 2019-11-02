<?php

namespace orangins\lib\infrastructure\customfield\parser;

use Exception;
use Phobject;

/**
 * Class PhabricatorCustomFieldMonogramParser
 * @package orangins\lib\infrastructure\customfield\parser
 * @author 陈妙威
 */
abstract class PhabricatorCustomFieldMonogramParser
    extends Phobject
{

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getPrefixes();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getSuffixes();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getInfixes();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getMonogramPattern();

    /**
     * @param $corpus
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public function parseCorpus($corpus)
    {
        $prefixes = $this->getPrefixes();
        $suffixes = $this->getSuffixes();
        $infixes = $this->getInfixes();

        $prefix_regex = $this->buildRegex($prefixes);
        $infix_regex = $this->buildRegex($infixes, true);
        $suffix_regex = $this->buildRegex($suffixes, true, true);

        $monogram_pattern = $this->getMonogramPattern();

        $pattern =
            '/' .
            '(?:^|\b)' .
            $prefix_regex .
            $infix_regex .
            '((?:' . $monogram_pattern . '(?:\b|$)[,\s]*)+)' .
            '(?:\band\s+(' . $monogram_pattern . '(?:\b|$)))?' .
            $suffix_regex .
            '(?:$|\b)' .
            '/';

        $matches = null;
        $ok = preg_match_all(
            $pattern,
            $corpus,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        if ($ok === false) {
            throw new Exception(pht('Regular expression "%s" is invalid!', $pattern));
        }

        $results = array();
        foreach ($matches as $set) {
            $monograms = array_filter(preg_split('/[,\s]+/', $set[3][0]));

            if (isset($set[4]) && $set[4][0]) {
                $monograms[] = $set[4][0];
            }

            $results[] = array(
                'match' => $set[0][0],
                'prefix' => $set[1][0],
                'infix' => $set[2][0],
                'monograms' => $monograms,
                'suffix' => idx(idx($set, 5, array()), 0, ''),
                'offset' => $set[0][1],
            );
        }

        return $results;
    }

    /**
     * @param array $list
     * @param bool $optional
     * @param bool $final
     * @return string
     * @author 陈妙威
     */
    private function buildRegex(array $list, $optional = false, $final = false)
    {
        $parts = array();
        foreach ($list as $string) {
            $parts[] = preg_quote($string, '/');
        }
        $parts = implode('|', $parts);

        $maybe_tail = $final ? '' : '\s+';
        $maybe_optional = $optional ? '?' : '';

        return '(?i:(' . $parts . ')' . $maybe_tail . ')' . $maybe_optional;
    }

}
