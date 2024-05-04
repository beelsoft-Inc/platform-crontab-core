<?php

namespace Tungnt\Crontab\Http\Controllers;

use Cron\CronExpression;
use Tungnt\Admin\Controllers\HasResourceActions;
use Tungnt\Admin\Form;
use Tungnt\Admin\Grid;
use Tungnt\Admin\Layout\Content;
use Tungnt\Crontab\Http\Models\Crontab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Routing\Controller;

class CrontabController extends Controller
{
    use HasResourceActions;

    const CRONTAB_TYPE = [
        'sql'=>'perform sql',
        'shell'=>'perform shell',
        'url'=>'question url'
    ];
    const CRONTAB_STATUS = [
        'normal'=>'normal',
        'disable'=>'disable',
        'completed'=>'completed',
        'expired'=>'expired'
    ];

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        $content->breadcrumb(
            ['text' => 'scheduled tasks', 'url' => '/crontabs'],
            ['text' => 'list']
        );
        return $content
            ->header('list')
            ->description('scheduled tasks')
            ->body($this->grid());
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        $content->breadcrumb(
            ['text' => 'scheduled tasks', 'url' => '/crontabs'],
            ['text' => 'edit']
        );
        return $content
            ->header('edit')
            ->description('scheduled tasks')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        $content->breadcrumb(
            ['text' => 'scheduled tasks', 'url' => '/crontabs'],
            ['text' => 'create']
        );
        return $content
            ->header('create')
            ->description('scheduled tasks')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Crontab);
        $grid->id('Id')->sortable();
        $grid->type('type')->using(self::CRONTAB_TYPE)->label('default');
        $grid->title('Task title');
        $grid->maximums('Maximum times');
        $grid->executes('Executed times')->sortable();
        $grid->execute_at('Next estimated time');
        $grid->end_at('last execution time')->sortable();
        $grid->status('status')->sortable()->using(self::CRONTAB_STATUS)->display(function ($status) {
            switch ($status){
                case 'normal':
                    return '<span class="label label-success">'.$status.'</span>';
                    break;
                case 'Disable':
                    return '<span class="label label-danger">'.$status.'</span>';
                    break;
                case 'Finish':
                    return '<span class="label label-info">'.$status.'</span>';
                    break;
                default :
                    return '<span class="label label-warning">'.$status.'</span>';
                    break;
            }
        });
        $grid->created_at('creation time');
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->filter(function($filter){
            $filter->disableIdFilter();
            $filter->like('title', 'Task title');
            $filter->equal('type', 'type')->select(self::CRONTAB_TYPE);

        });

        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Crontab);

        $form->text('title', 'Task title')->rules('required',['required'=>'Task title cannot be empty']);
        $form->select('type','Task type')->options(self::CRONTAB_TYPE)->help(".The URL type is the complete URL address, such as: <code>http://www.tungnt.vn/</code>;<br>2. If your server php.ini does not enable <code>shell_exec( )</code> function, URL type and Shell type patterns cannot be used!")->rules('required|in:url,sql,shell',['required'=>'Task type cannot be empty','in'=>'Parameter error']);
        $form->textarea('contents', 'Content')->rows(3)->rules('required',['required'=>'content require not null']);
        $form->text('schedule', 'execution cycle')->default('* * * * *')->help("Please use <code>Cron</code> expression")->rules(function ($form) {
            $value = $form->model()->schedule;
            if (empty($value)){
                return 'required';
            }
            if (!CronExpression::isValidExpression($value)){
//                return 'max:0';
            }
        },['required'=>'Execution cycle cannot be empty','max'=>'Execution cycle Cron expression error']);

        $form->html("<pre><code>*    *    *    *    *
-    -    -    -    -
|    |    |    |    +--- day of week (0 - 7) (Sunday=0 or 7)
|    |    |    +-------- month (1 - 12)
|    |    +------------- day of month (1 - 31)
|    +------------------ hour (0 - 23)
+----------------------- min (0 - 59)</code></pre>");
        $checkSchedule_url = url('admin/crontabs/checkSchedule');
        $js = <<<EOF
            <script type="text/javascript">
                 function checkSchedule() {
                    var schedule = $("#schedule").val();
                    $.post("{$checkSchedule_url}", {"schedule":schedule,_token:LA.token},
                    function(data){
                        if (data.status == false){
                            toastr.error(data.message);
                            return false;
                        }
                    }, "json");
                }

                $(function(){
                    checkSchedule();
                    $("#schedule,#begin_at").blur(function(){
                        checkSchedule();
                    });
                });
            </script>
EOF;
        $form->html($js);

        $form->number('maximums', 'Maximum number of executions')->default(0)->help("0 means unlimited times")->rules('required|integer|min:0',[
            'required'=>'The maximum number of executions cannot be empty',
            'integer'=>'The maximum number of executions must be a positive integer',
            'min'=>'The maximum number of executions cannot be negative',
        ]);
        $form->number('executes', 'Executed times')->default(0)->help("If the number of task executions reaches the upper limit, the status will be automatically changed to 'Completed'”
If the 'completed' task needs to be run again, please reset this parameter or adjust the maximum number of executions and change the following status value to 'normal'");
        $form->datetime('begin_at', 'Starting time')->default(date('Y-m-d H:i:s'))->help("If the start time is set, it is calculated from the start time;<br/>If the start time is not set, it is calculated based on the current time")->rules('required|date',['required'=>'Start time cannot be empty','date'=>'The time format is incorrect']);
        $form->datetime('end_at', 'End Time')->default(date('Y-m-d H:i:s'))->help("If long-term execution is required, please set the end time as long as possible")->rules('required|date',['required'=>'End time cannot be empty','date'=>'The time format is incorrect']);
        $form->number('weigh', 'Weights')->default(100)->help("When multiple tasks are executed at the same time, they are executed from high to low according to the weight.")->rules('required|integer',['required'=>'Weight cannot be empty','integer'=>'Weight must be a positive integer']);
        $form->select('status', 'status')->default('normal')->options(self::CRONTAB_STATUS)->rules('required|in:disable,normal,completed,expired',['required'=>'Status cannot be empty','in'=>'Parameter error']);

        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }

    /**
     * validate schedule.
     *
     * @return json
     */
    public function checkSchedule(Request $request){
        $schedule = $request->post('schedule','');
        if (empty($schedule)){
            return Response::json(['status'=>false,'message'=>'Execution cycle cannot be empty']);
        }
        if (!CronExpression::isValidExpression($schedule)){
            return Response::json(['status'=>false,'message'=>'Execution cycle Cron expression error']);
        }
        return Response::json(['status'=>true,'message'=>'']);
    }
}
