<?php

namespace orangins\lib\response;
/**
 * An object can implement this interface to allow it to be returned directly
 * from an @{class:AphrontController}.
 *
 * Normally, controllers must return an @{class:AphrontResponse}. Sometimes,
 * this is not convenient or requires an awkward API. If it's preferable to
 * return some other type of object which is equivalent to or describes a
 * valid response, that object can implement this interface and produce a
 * response later.
 */
interface AphrontResponseProducerInterface {


  /**
   * Produce the equivalent @{class:AphrontResponse} for this object.
   *
   * @return \orangins\lib\response\AphrontResponse Equivalent response.
   */
  public function produceAphrontResponse();
}
