<?php

namespace orangins\modules\herald\view;

use orangins\lib\view\AphrontView;

/**
 * Class HeraldWebhookRequestListView
 * @package orangins\modules\herald\view
 * @author 陈妙威
 */
final class HeraldWebhookRequestListView
    extends AphrontView
{

    /**
     * @var
     */
    private $requests;
    /**
     * @var
     */
    private $highlightID;

    /**
     * @param array $requests
     * @return $this
     * @author 陈妙威
     */
    public function setRequests(array $requests)
    {
        assert_instances_of($requests, 'HeraldWebhookRequest');
        $this->requests = $requests;
        return $this;
    }

    /**
     * @param $highlight_id
     * @return $this
     * @author 陈妙威
     */
    public function setHighlightID($highlight_id)
    {
        $this->highlightID = $highlight_id;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHighlightID()
    {
        return $this->highlightID;
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function render()
    {
        $viewer = $this->getViewer();
        $requests = $this->requests;

        $handle_phids = array();
        foreach ($requests as $request) {
            $handle_phids[] = $request->getObjectPHID();
        }
        $handles = $viewer->loadHandles($handle_phids);

        $highlight_id = $this->getHighlightID();

        $rows = array();
        $rowc = array();
        foreach ($requests as $request) {
            $icon = $request->newStatusIcon();

            if ($highlight_id == $request->getID()) {
                $rowc[] = 'highlighted';
            } else {
                $rowc[] = null;
            }

            $last_epoch = $request->getLastRequestEpoch();
            if ($request->getLastRequestEpoch()) {
                $last_request = phabricator_datetime($last_epoch, $viewer);
            } else {
                $last_request = null;
            }

            $rows[] = array(
                $request->getID(),
                $icon,
                $handles[$request->getObjectPHID()]->renderLink(),
                $request->getErrorTypeForDisplay(),
                $request->getErrorCodeForDisplay(),
                $last_request,
            );
        }

        $table = (new AphrontTableView($rows))
            ->setRowClasses($rowc)
            ->setHeaders(
                array(
                    pht('ID'),
                    null,
                    pht('Object'),
                    pht('Type'),
                    pht('Code'),
                    pht('Requested At'),
                ))
            ->setColumnClasses(
                array(
                    'n',
                    '',
                    'wide',
                    '',
                    '',
                    '',
                ));

        return $table;
    }

}
