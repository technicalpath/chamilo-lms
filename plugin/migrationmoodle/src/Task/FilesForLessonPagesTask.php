<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\BaseExtractor;

/**
 * Class FilesForLessonPagesTask.
 *
 * Task for migrate the files for Moodle lesson pages in the files for Chamilo course documents.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class FilesForLessonPagesTask extends CourseFilesTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => BaseExtractor::class,
            'query' => "SELECT
                    f.id,
                    f.contenthash,
                    f.filepath,
                    f.filename,
                    f.filesize,
                    f.mimetype,
                    cm.course
                FROM mdl_files f
                INNER JOIN mdl_context c ON f.contextid = c.id
                INNER JOIN mdl_course_modules cm ON c.instanceid = cm.id
                WHERE f.component = 'mod_lesson'
                    AND f.filearea = 'page_contents'
                    AND c.contextlevel = 70
                    AND f.filename NOT IN ('.', '..')",
        ];
    }
}
