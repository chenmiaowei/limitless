<?php

/**
 * Class PhutilAWSS3DeleteManagementWorkflow
 * @author 陈妙威
 */
final class PhutilAWSS3DeleteManagementWorkflow
    extends PhutilAWSS3ManagementWorkflow
{

    /**
     * @return null|void
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('delete')
            ->setExamples(
                '**delete** --key __key__')
            ->setSynopsis(pht('Delete an object from S3.'))
            ->setArguments(
                array_merge(
                    $this->getAWSArguments(),
                    $this->getAWSS3BucketArguments(),
                    array(
                        array(
                            'name' => 'key',
                            'param' => 'key',
                            'help' => pht('Specify a key to delete.'),
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

        $future = $this
            ->newAWSFuture(new PhutilAWSS3Future())
            ->setParametersForDeleteObject($key);

        $future->resolve();

        echo tsprintf(
            "%s\n",
            pht('Deleted "%s".', $key));

        return 0;
    }

}
