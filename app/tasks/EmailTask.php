<?php

declare(strict_types=1);

use Phalcon\Cli\Task;
use Phalcon\Db\Enum as DbEnum;

class EmailTask extends Task
{

    public function mainAction(): void
    {
        echo "This is the main action of the EmailTask.\n";
    }

    public function sendEmailAction()
    {
        echo "Starting Email Sending...\n";

        $this->errorService = $this->di->get('errorService');
        $emailService = $this->di->get('emailService');

        for ($i = 0; $i < 10; $i++) {
            $res = $emailService->sendInternal();
            if ($this->errorService->isError($res)) {
                echo "Error: " . $res['error_code'] . " - " . $res['error_message'] . "\n";
                break;
            }
            if (!empty($res['no_work'])) {
                echo "No more emails to send.\n";
                break;
            }
            echo "Sent email: " . ($res['email_id'] ?? 'n/a') . "\n";
        }

    }


}
