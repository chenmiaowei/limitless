<?php

namespace orangins\modules\search\query;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\OranginsObject;
use orangins\lib\view\phui\PHUITagView;
use orangins\modules\widgets\javelin\JavelinTooltipAsset;
use PhutilSearchQueryCompiler;
use PhutilSearchQueryToken;

/**
 * Class PhabricatorFulltextToken
 * @package orangins\modules\search\query
 * @author 陈妙威
 */
final class PhabricatorFulltextToken extends OranginsObject
{

    /**
     * @var
     */
    private $token;
    /**
     * @var
     */
    private $isShort;
    /**
     * @var
     */
    private $isStopword;

    /**
     * @param PhutilSearchQueryToken $token
     * @return $this
     * @author 陈妙威
     */
    public function setToken(PhutilSearchQueryToken $token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isQueryable()
    {
        return !$this->getIsShort() && !$this->getIsStopword();
    }

    /**
     * @param $is_short
     * @return $this
     * @author 陈妙威
     */
    public function setIsShort($is_short)
    {
        $this->isShort = $is_short;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsShort()
    {
        return $this->isShort;
    }

    /**
     * @param $is_stopword
     * @return $this
     * @author 陈妙威
     */
    public function setIsStopword($is_stopword)
    {
        $this->isStopword = $is_stopword;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsStopword()
    {
        return $this->isStopword;
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function newTag()
    {
        $token = $this->getToken();

        $tip = null;
        $icon = null;

        if ($this->getIsShort()) {
            $shade = PHUITagView::COLOR_GREY;
            $tip = pht('Ignored Short Word');
        } else if ($this->getIsStopword()) {
            $shade = PHUITagView::COLOR_GREY;
            $tip = pht('Ignored Common Word');
        } else {
            $operator = $token->getOperator();
            switch ($operator) {
                case PhutilSearchQueryCompiler::OPERATOR_NOT:
                    $shade = PHUITagView::COLOR_DANGER;
                    $icon = 'fa-minus';
                    break;
                case PhutilSearchQueryCompiler::OPERATOR_SUBSTRING:
                    $tip = pht('Substring Search');
                    $shade = PHUITagView::COLOR_VIOLET;
                    break;
                case PhutilSearchQueryCompiler::OPERATOR_EXACT:
                    $tip = pht('Exact Search');
                    $shade = PHUITagView::COLOR_GREEN;
                    break;
                default:
                    $shade = PHUITagView::COLOR_BLUE;
                    break;
            }
        }

        $tag = (new PHUITagView())
            ->setType(PHUITagView::TYPE_SHADE)
            ->setColor($shade)
            ->setName($token->getValue());

        if ($tip !== null) {
            JavelinHtml::initBehavior(new JavelinTooltipAsset());

            $tag
                ->addSigil('has-tooltip')
                ->setMetadata(
                    array(
                        'tip' => $tip,
                    ));
        }

        if ($icon !== null) {
            $tag->setIcon($icon);
        }

        return $tag;
    }

}
