<?php

namespace orangins\modules\transactions\actions;

use orangins\lib\response\Aphront400Response;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\phid\query\PhabricatorObjectQuery;

/**
 * Class PhabricatorApplicationTransactionCommentQuoteController
 * @package orangins\modules\transactions\actions
 * @author 陈妙威
 */
final class PhabricatorApplicationTransactionCommentQuoteController
    extends PhabricatorApplicationTransactionController
{

    /**
     * @return AphrontAjaxResponse|Aphront400Response|Aphront404Response
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $phid = $request->getURIData('phid');

        $xaction = (new PhabricatorObjectQuery())
            ->withPHIDs(array($phid))
            ->setViewer($viewer)
            ->executeOne();
        if (!$xaction) {
            return new Aphront404Response();
        }

        if (!$xaction->getComment()) {
            return new Aphront404Response();
        }

        if ($xaction->getComment()->getIsRemoved()) {
            return new Aphront400Response();
        }

        if (!$xaction->hasComment()) {
            return new Aphront404Response();
        }

        $content = $xaction->getComment()->getContent();
        $content = rtrim($content, "\r\n");
        $content = phutil_split_lines($content, true);
        foreach ($content as $key => $line) {
            if (strlen($line) && ($line[0] != '>')) {
                $content[$key] = '> ' . $line;
            } else {
                $content[$key] = '>' . $line;
            }
        }
        $content = implode('', $content);

        $author = (new PhabricatorHandleQuery())
            ->setViewer($viewer)
            ->withPHIDs(array($xaction->getComment()->getAuthorPHID()))
            ->executeOne();

        $ref = $request->getStr('ref');
        if (strlen($ref)) {
            $quote = \Yii::t("app", 'In {0}, {1} wrote:', [
                $ref,
                '@' . $author->getName()
            ]);
        } else {
            $quote = \Yii::t("app", '{0} wrote:', [
                '@' . $author->getName()
            ]);
        }

        $content = ">>! {$quote}\n{$content}";

        return (new AphrontAjaxResponse())->setContent(
            array(
                'quoteText' => $content,
            ));
    }

}
