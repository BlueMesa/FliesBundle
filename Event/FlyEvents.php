<?php

/*
 * This file is part of the FliesBundle.
 * 
 * Copyright (c) 2016 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Bluemesa\Bundle\FliesBundle\Event;


final class FlyEvents
{
    /**
     * @Event
     */
    const VIALS_CREATED = 'bluemesa.flies.vials_created';


    /**
     * @Event
     */
    const EXPAND_INITIALIZE = 'bluemesa.flies.expand_initialize';

    /**
     * @Event
     */
    const EXPAND_SUBMITTED = 'bluemesa.flies.expand_submitted';

    /**
     * @Event
     */
    const EXPAND_SUCCESS = 'bluemesa.flies.expand_success';

    /**
     * @Event
     */
    const EXPAND_COMPLETED = 'bluemesa.flies.expand_completed';


    /**
     * @Event
     */
    const FLIP_INITIALIZE = 'bluemesa.flies.flip_initialize';

    /**
     * @Event
     */
    const FLIP_SUBMITTED = 'bluemesa.flies.flip_submitted';

    /**
     * @Event
     */
    const FLIP_SUCCESS = 'bluemesa.flies.flip_success';

    /**
     * @Event
     */
    const FLIP_COMPLETED = 'bluemesa.flies.flip_completed';


    /**
     * @Event
     */
    const TRASH_INITIALIZE = 'bluemesa.flies.trash_initialize';

    /**
     * @Event
     */
    const TRASH_SUBMITTED = 'bluemesa.flies.trash_submitted';

    /**
     * @Event
     */
    const TRASH_SUCCESS = 'bluemesa.flies.trash_success';

    /**
     * @Event
     */
    const TRASH_COMPLETED = 'bluemesa.flies.trash_completed';


    /**
     * @Event
     */
    const UNTRASH_INITIALIZE = 'bluemesa.flies.untrash_initialize';

    /**
     * @Event
     */
    const UNTRASH_SUBMITTED = 'bluemesa.flies.untrash_submitted';

    /**
     * @Event
     */
    const UNTRASH_SUCCESS = 'bluemesa.flies.untrash_success';

    /**
     * @Event
     */
    const UNTRASH_COMPLETED = 'bluemesa.flies.untrash_completed';
}
