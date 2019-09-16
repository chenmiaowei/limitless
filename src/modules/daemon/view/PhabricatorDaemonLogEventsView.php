<?php

namespace orangins\modules\daemon\view;

use orangins\lib\view\AphrontView;
use orangins\lib\view\control\AphrontTableView;
use PhutilNumber;

/**
 * Class PhabricatorDaemonLogEventsView
 * @package orangins\modules\daemon\view
 * @author 陈妙威
 */
final class PhabricatorDaemonLogEventsView extends AphrontView
{

    /**
     * @var
     */
    private $events;
    /**
     * @var
     */
    private $combinedLog;
    /**
     * @var
     */
    private $showFullMessage;


    /**
     * @param $show_full_message
     * @return $this
     * @author 陈妙威
     */
    public function setShowFullMessage($show_full_message)
    {
        $this->showFullMessage = $show_full_message;
        return $this;
    }

    /**
     * @param array $events
     * @return $this
     * @author 陈妙威
     */
    public function setEvents(array $events)
    {
        assert_instances_of($events, 'PhabricatorDaemonLogEvent');
        $this->events = $events;
        return $this;
    }

    /**
     * @param $is_combined
     * @return $this
     * @author 陈妙威
     */
    public function setCombinedLog($is_combined)
    {
        $this->combinedLog = $is_combined;
        return $this;
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {
        $viewer = $this->getViewer();
        $rows = array();

        foreach ($this->events as $event) {

            // Limit display log size. If a daemon gets stuck in an output loop this
            // page can be like >100MB if we don't truncate stuff. Try to do cheap
            // line-based truncation first, and fall back to expensive UTF-8 character
            // truncation if that doesn't get things short enough.

            $message = $event->getMessage();
            $more = null;

            if (!$this->showFullMessage) {
                $more_lines = null;
                $more_chars = null;
                $line_limit = 12;
                if (substr_count($message, "\n") > $line_limit) {
                    $message = explode("\n", $message);
                    $more_lines = count($message) - $line_limit;
                    $message = array_slice($message, 0, $line_limit);
                    $message = implode("\n", $message);
                }

                $char_limit = 8192;
                if (strlen($message) > $char_limit) {
                    $message = phutil_utf8v($message);
                    $more_chars = count($message) - $char_limit;
                    $message = array_slice($message, 0, $char_limit);
                    $message = implode('', $message);
                }

                if ($more_chars) {
                    $more = new PhutilNumber($more_chars);
                    $more = \Yii::t("app",'Show %d more character(s)...', $more);
                } else if ($more_lines) {
                    $more = new PhutilNumber($more_lines);
                    $more = \Yii::t("app",'Show %d more line(s)...', $more);
                }

                if ($more) {
                    $id = $event->getID();
                    $more = array(
                        "\n...\n",
                        phutil_tag(
                            'a',
                            array(
                                'href' => "/daemon/event/{$id}/",
                            ),
                            $more),
                    );
                }
            }

            $row = array(
                $event->getLogType(),
                phabricator_date($event->getEpoch(), $viewer),
                phabricator_time($event->getEpoch(), $viewer),
                array(
                    $message,
                    $more,
                ),
            );

            if ($this->combinedLog) {
                array_unshift(
                    $row,
                    phutil_tag(
                        'a',
                        array(
                            'href' => '/daemon/log/' . $event->getLogID() . '/',
                        ),
                        \Yii::t("app",'Daemon %s', $event->getLogID())));
            }

            $rows[] = $row;
        }

        $classes = array(
            '',
            '',
            'right',
            'wide prewrap',
        );

        $headers = array(
            'Type',
            'Date',
            'Time',
            'Message',
        );

        if ($this->combinedLog) {
            array_unshift($classes, 'pri');
            array_unshift($headers, 'Daemon');
        }

        $log_table = new AphrontTableView($rows);
        $log_table->setHeaders($headers);
        $log_table->setColumnClasses($classes);

        return $log_table->render();
    }

}
