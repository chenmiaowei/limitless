<?php

/**
 * Class PhutilAWSS3PutManagementWorkflow
 * @author 陈妙威
 */
final class PhutilAWSS3PutManagementWorkflow
    extends PhutilAWSS3ManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('put')
            ->setExamples(
                '**put** --key __key__')
            ->setSynopsis(pht('Upload content to S3.'))
            ->setArguments(
                array_merge(
                    $this->getAWSArguments(),
                    $this->getAWSS3BucketArguments(),
                    array(
                        array(
                            'name' => 'key',
                            'param' => 'key',
                            'help' => pht('Specify a key to upload.'),
                        ),
                    )));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws PhutilArgumentSpecificationException
     * @throws PhutilArgumentUsageException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $key = $args->getArg('key');
        if (!strlen($key)) {
            throw new PhutilArgumentUsageException(
                pht(
                    'Specify an AWS S3 object key to access with --key.'));
        }

        $future = $this->newAWSFuture(new PhutilAWSS3Future());

        echo tsprintf(
            "%s\n",
            pht('Reading data from stdin...'));

        $data = file_get_contents('php://stdin');

        $future->setParametersForPutObject($key, $data);

        $result = $future->resolve();

        echo tsprintf(
            "%s\n",
            pht('Uploaded "%s".', $key));

        return 0;
    }

}
