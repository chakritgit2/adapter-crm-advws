<?php
declare(strict_types=1);

use Phalcon\Mvc\Controller;

class ActionController extends Controller
{
    /**
     * Approves a pending audit alert, pushing it into the API execution queue.
     * Expects a POST request with 'alert_id' and 'target_entity'.
     */
    public function approveAlertAction()
    {
        // 1. Ensure this is a POST request for security
        if (!$this->request->isPost()) {
            return $this->response->setStatusCode(405)->setJsonContent([
                'status'  => 'error',
                'message' => 'Method not allowed. Please use POST.'
            ]);
        }

        // 2. Grab inputs from the frontend request
        $alertId      = $this->request->getPost('alert_id', 'int');
        $targetEntity = $this->request->getPost('target_entity', 'string');

        // 3. Map the target entity to the correct strict database table
        $tableMapping = [
            'account'  => 'account_audit_alerts',
            'campaign' => 'campaign_audit_alerts',
            'ad_group' => 'ad_group_audit_alerts',
            'keyword'  => 'keyword_audit_alerts',
            'ad'       => 'ad_audit_alerts'
        ];

        if (!array_key_exists($targetEntity, $tableMapping)) {
            return $this->response->setStatusCode(400)->setJsonContent([
                'status'  => 'error',
                'message' => 'Invalid target entity specified.'
            ]);
        }

        $targetTable = $tableMapping[$targetEntity];

        try {
            // 4. Update the execution status using Phalcon's raw database adapter
            // We ensure we only approve alerts that are currently under 'manual_review'
            $sql = "UPDATE {$targetTable} 
                    SET execution_status = 'approved_for_queue' 
                    WHERE id = :alert_id 
                      AND execution_status = 'manual_review'
                      AND status = 'active'";

            $success = $this->db->execute($sql, [
                'alert_id' => $alertId
            ]);

            // 5. Check if any rows were actually updated
            if ($this->db->affectedRows() > 0) {
                return $this->response->setJsonContent([
                    'status'  => 'success',
                    'message' => ucfirst($targetEntity) . ' action approved and sent to the API queue!'
                ]);
            } else {
                return $this->response->setStatusCode(404)->setJsonContent([
                    'status'  => 'warning',
                    'message' => 'Alert not found, or it has already been approved/processed.'
                ]);
            }

        } catch (\Exception $e) {
            // Catch database errors (e.g., deadlocks or connection drops)
            return $this->response->setStatusCode(500)->setJsonContent([
                'status'  => 'error',
                'message' => 'Failed to approve alert: ' . $e->getMessage()
            ]);
        }
    }
}