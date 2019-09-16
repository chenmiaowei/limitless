<?php

namespace orangins\modules\transactions\constants;

use orangins\lib\OranginsObject;

final class PhabricatorTransactions extends OranginsObject
{
    const TYPE_COMMENT = 'core:comment';
    const TYPE_SUBSCRIBERS = 'core:subscribers';
    const TYPE_VIEW_POLICY = 'core:view-policy';
    const TYPE_EDIT_POLICY = 'core:edit-policy';
    const TYPE_JOIN_POLICY = 'core:join-policy';
    const TYPE_EDGE = 'core:edge';
    const TYPE_CUSTOMFIELD = 'core:customfield';
    const TYPE_TOKEN = 'token:give';
    const TYPE_INLINESTATE = 'core:inlinestate';
    const TYPE_SPACE = 'core:space';
    const TYPE_CREATE = 'core:create';
    const TYPE_COLUMNS = 'core:columns';
    const TYPE_SUBTYPE = 'core:subtype';
    const TYPE_HISTORY = 'core:history';
    const TYPE_MFA = 'core:mfa';

    const COLOR_RED = 'red';
    const COLOR_ORANGE = 'orange';
    const COLOR_YELLOW = 'yellow';
    const COLOR_GREEN = 'green';
    const COLOR_SKY = 'sky';
    const COLOR_BLUE = 'blue';
    const COLOR_INDIGO = 'indigo';
    const COLOR_VIOLET = 'violet';
    const COLOR_GREY = 'grey';
    const COLOR_BLACK = 'black';
}
