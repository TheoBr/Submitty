<?php
use \models\User;

use \models\ElectronicGradeable;

    include "../header.php";

    $account_subpages_unlock = true;

    $g_id = $_GET['g_id'];

    $params = array($g_id);
    
    //get the total score for the gradeable
    $db->query("
SELECT 
    g.g_id, 
    g_title,
    g_grade_by_registration,
    sum(gc_max_value) as score 
FROM 
    gradeable AS g INNER JOIN gradeable_component AS gc ON g.g_id=gc.g_id
WHERE 
    g.g_id=?
    AND NOT gc.gc_is_extra_credit
GROUP BY 
    g.g_id", $params);
    $gradeable_info = $db->row();
    
// students and their grade data
$query = "
SELECT
    s.*,
    gt.g_id,
    gt.score
FROM
    users AS s
    LEFT JOIN (
        SELECT 
           g_id, 
           gd_user_id,
           sum(gcd_score) AS score
        FROM 
            gradeable_data AS gd INNER JOIN gradeable_component_data AS gcd ON gd.gd_id = gcd.gd_id
        WHERE g_id = ?
        GROUP BY 
            g_id, 
            gd_user_id
    ) as gt ON gt.gd_user_id=s.user_id";

print <<<HTML
    <script type="text/javascript">
    if (window.location.hash != "") {
        window.scrollTo(0, 0);
        setTimeout(function() {
            window.scrollTo(0, 0);
        }, 1);
    }
    $(function() {
        if (window.location.hash != "") {
            if ($(window.location.hash).offset().top > 0) {
                $("html, body").animate({scrollTop: ($(window.location.hash).offset().top - 40)}, 800);
            }
        }
    });
    </script>
    <style type="text/css">
        body {
            overflow: scroll;
        }

        #container-rubric
        {
            width:700px;
            margin: 70px auto 100px;
            background-color: #fff;
            border: 1px solid #999;
            border: 1px solid rgba(0,0,0,0.3);
            -webkit-border-radius: 6px;
            -moz-border-radius: 6px;
            border-radius: 6px;outline: 0;
            -webkit-box-shadow: 0 3px 7px rgba(0,0,0,0.3);
            -moz-box-shadow: 0 3px 7px rgba(0,0,0,0.3);
            box-shadow: 0 3px 7px rgba(0,0,0,0.3);
            -webkit-background-clip: padding-box;
            -moz-background-clip: padding-box;
            background-clip: padding-box;
        }
    </style>
HTML;

if (!isset($gradeable_info['g_id'])) {
    print <<<HTML
    <div id="container-rubric">
        <div class="modal-header">
            <h3 id="myModalLabel">Invalid Gradeable</h3>
        </div>

        <div class="modal-body" style="padding-bottom:10px; padding-top:25px;">
            Could not find a gradeable with that ID.<br /><br />
            <a class="btn" href="{$BASE_URL}/account/index.php?course={$_GET['course']}&semester={$_GET['semester']}">Select Different Gradeable</a>
        </div>
    </div>
HTML;
}
else {
    if (!User::$is_administrator) {
        if (isset($_GET['all']) && $_GET['all'] == "true") {
            $button = "<a class='btn' href='{$BASE_URL}/account/account-summary.php?g_id={$g_id}&course={$_GET['course']}&semester={$_GET['semester']}'>View Your Sections</a>";
        }
        else {
            $button = "<a class='btn' href='{$BASE_URL}/account/account-summary.php?g_id={$g_id}&course={$_GET['course']}&semester={$_GET['semester']}&all=true'>View All Sections</a>";
        }
    }
    else {
        $button = "";
    }
    
    $rubric_total = $gradeable_info["score"];
    
    print <<<HTML
    <div id="container-rubric">
        <div class="modal-header">
            <h3 id="myModalLabel" style="width: 75%; display: inline-block">{$gradeable_info['g_title']} Summary</h3>
            {$button}
        </div>

        <div class="modal-body" style="padding-bottom:10px; padding-top:25px;">
            <table class="table table-bordered" id="rubricTable" style=" border: 1px solid #AAA;">
                <thead style="background: #E1E1E1;">
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Autograding</th>
                        <th>TA Grading</th>
                    </tr>
                </thead>

                <tbody style="background: #f9f9f9;">
HTML;

    
    $where = array();
    $order = array();

    $grade_by_reg_section = $gradeable_info['g_grade_by_registration'];
    $section_title = ($grade_by_reg_section ? 'Registration': 'Rotating');
    $enrolled_assignment = ($grade_by_reg_section ? "enrolled in": "assigned to");
    $user_section_field =  ($grade_by_reg_section ? 'registration_section': 'rotating_section');
    $section_section_field = ($grade_by_reg_section ? 'sections_registration_id': 'sections_rotating');
    
    if(!((isset($_GET["all"]) && $_GET["all"] == "true") || $user_is_administrator == true)) {

        if ($grade_by_reg_section) {
            $params = array($user_id);
            $s_query = "SELECT sections_registration_id FROM grading_registration WHERE user_id=? ORDER BY sections_registration_id";
            $db->query($s_query, $params);
        }
        else {
            $params = array($user_id,$g_id);
            $s_query = "SELECT sections_rotating FROM grading_rotating WHERE user_id=? AND g_id=? ORDER BY sections_rotating";
            $db->query($s_query, $params);
        }

        $sections = array();
        foreach ($db->rows() as $section) {
            $sections[] = $section[$section_section_field];
        }
        if(count($sections) > 0){
            $where[] = "s.".$user_section_field." IN (" . implode(",", $sections) . ")";
        }
        else {
            $where[] = "s.user_id = null";
        }
    }
    
    $order[] = "s.".$user_section_field;
    $order[] = "s.user_id";

    if(count($where) > 0) {
        $query .= " WHERE " . implode(" AND ", $where);
    }
    if(count($order) > 0) {
        $query .= " ORDER BY " . implode(",", $order);
    }

    $prev_section = null;

    $params = array($g_id);
    
    $db->query($query, $params);
    $students = $db->rows();

    foreach ($students as $student) {
        $eg = new ElectronicGradeable($student["user_id"], $g_id);
        $section_id = ($student[$user_section_field]) !== null ? intval($student[$user_section_field]) : null;
        if($prev_section !== $section_id) {
            if ($section_id === null) {
                $to_show = "NULL {$section_title} Section";
            }
            else {
                $to_show = "{$section_title} Section {$section_id}";
            }
            print <<<HTML

                <tr class="info">
                    <td colspan="4" style="text-align:center;">
          Students {$enrolled_assignment} {$to_show}
                    </td>
                </tr>
HTML;
            $prev_section = $section_id;
        }
        $row = $student;
        $firstname = getDisplayName($student);
        print <<<HTML
                <tr id="user-row-{$student['user_id']}">
                    <td>
                        {$student["user_id"]}
                    </td>
                    <td>
                        {$firstname} {$student["user_lastname"]}
                    </td>
                    <td>
                        {$eg->autograding_points} / {$eg->autograding_max}
                    </td>
                    <td>
HTML;
        if(isset($row['score'])) {
            if($row['score'] >= 0) {
                echo "<a class='btn' href='{$BASE_URL}/account/index.php?g_id=" . $_GET["g_id"] . "&individual=" . $student["user_id"] . "&course={$_GET['course']}&semester={$_GET['semester']}'>[ " . ($row['score']).
                       " / " . ($rubric_total) . " ]</a>";
            }
            else {
                echo "<a class='btn btn-danger' href='{$BASE_URL}/account/index.php?g_id=" . $_GET["g_id"] . "&individual=" . $student["user_id"] . "&course={$_GET['course']}&semester={$_GET['semester']}'>[ GRADING ERROR ]</a>";
            }
        }
        else {
            echo "<a class='btn btn-primary' href='{$BASE_URL}/account/index.php?g_id=" . $_GET["g_id"] . "&individual=" . $student["user_id"] . "&course={$_GET['course']}&semester={$_GET['semester']}'>Grade</a>";
        }
        print <<<HTML
                    </td>
                </tr>
HTML;
    }
    print <<<HTML
                </tbody>
            </table>
        </div>

        <div class="modal-footer">
            <!--<a class="btn" href="{$BASE_URL}/account/index.php?course={$_GET['course']}&semester={$_GET['semester']}">Select Different Homework</a>-->
            <a class="btn" href="{$BASE_URL}/account/index.php?g_id={$_GET['g_id']}&course={$_GET['course']}&semester={$_GET['semester']}">Grade Next Student</a>
        </div>
    </div>
HTML;

    print <<<HTML
    <script type="text/javascript">
        createCookie('backup',0,1000);
    </script>
HTML;
}

include "../footer.php";