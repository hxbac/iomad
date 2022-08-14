<?php 

/**
 * Serve the files from the myplugin file areas.
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function giaoandientu_pluginfile(
    $course,
    $cm,
    $context,
    string $filearea,
    array $args,
    bool $forcedownload,
    array $options = []
): bool {
    global $DB;

    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'giaovien') {
        return false;
    }

    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    // require_login($course, true);

    // Check the relevant capabilities - these may vary depending on the filearea being accessed.
    // if (!has_capability('mod/giaoandientu:view', $context)) {
    //     return false;
    // }

    // The args is an array containing [itemid, path].
    // Fetch the itemid from the path.
    $itemid = array_shift($args);
    // For a plugin which does not specify the itemid, you may want to use:

    // Extract the filename / filepath from the $args array.
    // $filename = array_pop($args); // The last item in the $args array.
    // if (empty($args)) {
    //     // $args is empty => the path is '/'.
    //     $filepath = '/';
    // } else {
    //     // $args contains the remaining elements of the filepath.
    //     $filepath = '/' . implode('/', $args) . '/';
    // }

    $relativepath = implode('/', $args);

    $fullpath = "/{$context->id}/mod_giaoandientu/$filearea/$itemid/$relativepath";

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // Retrieve the file from the Files API.
    // $fs = get_file_storage();

    // $file = $fs->get_file($context->id, 'mod_giaoandientu', $filearea, $itemid, $filepath, $filename);
    // if (!$file) {
    //     // The file does not exist.
    //     return false;
    // }
    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    
    send_stored_file($file, 0, 0, $forcedownload, $options);
}