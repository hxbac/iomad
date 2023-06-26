<?php
// …

namespace assignsubmission_estream\privacy;

class provider implements

public static function get_metadata(collection $collection): collection {

    $collection->add_database_table(
        'assignsubmission_estream',
         [
            'assignment' => 'privacy:metadata:assignsubmission_estream:assignment',
            'submission' => 'privacy:metadata:assignsubmission_estream:submission',
            'cdid' => 'privacy:metadata:assignsubmission_estream:cdid',
			'embedcode' => 'privacy:metadata:assignsubmission_estream:embedcode',

         ],
        'privacy:metadata:assignsubmission_estream'
    );

    return $collection;
}

public static function get_metadata(collection $collection): collection {
    $collection->add_external_location_link('assignsubmission_estream', [
            'userid' => 'privacy:metadata:assignsubmission_estream:userid',
            'fullname' => 'privacy:metadata:assignsubmission_estream:fullname',
		    'email' => 'privacy:metadata:assignsubmission_estream:email',
            'userip' => 'privacy:metadata:assignsubmission_estream:userip',
        ], 'privacy:metadata:assignsubmission_estream');

    return $collection;
}

}