<?php

namespace app\controllers;

use app\controllers\admin\GradeablesController;
use app\controllers\admin\ConfigurationController;
use app\controllers\admin\UsersController;
use app\libraries\Core;
use app\libraries\Output;
use app\models\User;

class AdminController extends AbstractController {
    public function run() {
        if (!$this->core->getUser()->accessAdmin()) {
            $this->core->getOutput()->showError("This account cannot access admin pages");
        }

        $this->core->getOutput()->addBreadcrumb("Admin");
        $controller = null;
        switch ($_REQUEST['page']) {
            case 'users':
                $controller = new UsersController($this->core);
                break;
            case 'configuration':
                $this->core->getOutput()->addBreadcrumb("Course Settings");
                $controller = new ConfigurationController($this->core);
                break;
            default:
                $this->core->getOutput()->showError("Invalid page request for controller ".get_class($this));
                break;
        }
        $controller->run();
    }
}