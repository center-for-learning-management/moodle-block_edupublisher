<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    block_edupublisher
 * @copyright  2020 Center for Learningmangement (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edupublisher;

defined('MOODLE_INTERNAL') || die;

class template_resolver {
    private $last = null;

    public function __construct(private object $parentObject, private array|object $otherData = []) {
        $this->otherData = (object)$this->otherData;
    }

    public function __isset(string $name) {
        $name = preg_replace('!^.*:!', '', $name);

        $this->last = $name;
        if (in_array($name, ['str', 'uniqid'])) {
            // don't handle template helpers
            return false;
        }
        if (in_array($name, ['wwwroot'])) {
            return true;
        }

        if (method_exists($this->parentObject, $name)) {
            return true;
        }

        if (isset($this->otherData->{$name})) {
            return true;
        }

        if (isset($this->parentObject->{$name})) {
            return true;
        }

        if (method_exists($this->parentObject, 'template_get')) {
            return $this->parentObject->template_get($name) !== null;
        }

        if (method_exists($this->parentObject, 'get')) {
            return $this->parentObject->get($name) !== null;
        }

        return false;
    }

    // public function comma_seperated_list() {
    //     echo 'kkk';
    //         $this->last;
    //     return true;
    // }

    public function __get(string $name) {
        $orig_name = $name;
        if (preg_match('!^(.*):(.*)!', $name, $matches)) {
            $modifier = $matches[1];
            $name = $matches[2];
        } else {
            $modifier = '';
        }

        $value = $this->__get_internal($name);

        if (!$modifier) {
            return $value;
        } elseif ($modifier == 'comma_seperated_list') {
            if (empty($value)) {
                return '';
            }
            if (!is_array($value)) {
                throw new \moodle_exception("not an array: '{$orig_name}'");
            }

            return join(', ', $value);
        } else {
            throw new \moodle_exception("unknown modifier '{$modifier}'");
        }
    }

    private function __get_internal(string $name) {
        global $CFG;

        if (in_array($name, ['wwwroot'])) {
            return $CFG->{$name};
        }

        if (method_exists($this->parentObject, $name)) {
            return call_user_func([$this->parentObject, $name]);
        }

        if (isset($this->otherData->{$name})) {
            return $this->otherData->{$name};
        }

        if (isset($this->parentObject->{$name})) {
            return $this->parentObject->{$name};
        }

        if (method_exists($this->parentObject, 'template_get')) {
            return $this->parentObject->template_get($name);
        }

        if (method_exists($this->parentObject, 'get')) {
            return $this->parentObject->get($name);
        }

        return null;
    }
}
