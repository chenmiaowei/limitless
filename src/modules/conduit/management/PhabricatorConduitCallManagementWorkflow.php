<?php

namespace orangins\modules\conduit\management;

use Filesystem;
use orangins\modules\conduit\call\ConduitCall;
use orangins\modules\people\models\PhabricatorUser;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use PhutilJSON;
use Yii;

/**
 * Class PhabricatorConduitCallManagementWorkflow
 * @package orangins\modules\conduit\management
 * @author 陈妙威
 */
final class PhabricatorConduitCallManagementWorkflow
    extends PhabricatorConduitManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('call')
            ->setSynopsis(Yii::t("app",'Call a Conduit method..'))
            ->setArguments(
                array(
                    array(
                        'name' => 'method',
                        'param' => 'method',
                        'help' => Yii::t("app",'Method to call.'),
                    ),
                    array(
                        'name' => 'input',
                        'param' => 'input',
                        'help' => Yii::t("app",
                            'File to read parameters from, or "-" to read from ' .
                            'stdin.'),
                    ),
                    array(
                        'name' => 'as',
                        'param' => 'username',
                        'help' => Yii::t("app",
                            'Execute the call as the given user. (If omitted, the call will ' .
                            'be executed as an omnipotent user.)'),
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws PhutilArgumentUsageException
     * @throws \FilesystemException
     * @throws \PhutilArgumentSpecificationException
     * @throws \orangins\modules\conduit\protocol\exception\ConduitException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $viewer = $this->getViewer();

        $method = $args->getArg('method');
        if (!strlen($method)) {
            throw new PhutilArgumentUsageException(
                Yii::t("app",'Specify a method to call with "--method".'));
        }

        $input = $args->getArg('input');
        if (!strlen($input)) {
            throw new PhutilArgumentUsageException(
                Yii::t("app",'Specify a file to read parameters from with "--input".'));
        }

        $as = $args->getArg('as');
        if (strlen($as)) {
            $actor = PhabricatorUser::find()
                ->setViewer($viewer)
                ->withUsernames(array($as))
                ->executeOne();
            if (!$actor) {
                throw new PhutilArgumentUsageException(
                    Yii::t("app",
                        'No such user "%s" exists.',
                        $as));
            }
        } else {
            $actor = $viewer;
        }

        if ($input === '-') {
            fprintf(STDERR, tsprintf("%s\n", Yii::t("app",'Reading input from stdin...')));
            $input_json = file_get_contents('php://stdin');
        } else {
            $input_json = Filesystem::readFile($input);
        }

        $params = phutil_json_decode($input_json);

        $result = (new ConduitCall($method, $params))
            ->setUser($actor)
            ->execute();

        $output = array(
            'result' => $result,
        );

        echo tsprintf(
            "%B\n",
            (new PhutilJSON())->encodeFormatted($output));

        return 0;
    }

}
