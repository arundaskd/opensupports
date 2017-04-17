<?php
use Respect\Validation\Validator as DataValidator;

/**
 * @api {post} /ticket/change-priority Change the priority of a ticket.
 *
 * @apiName Change priority
 *
 * @apiGroup Ticket
 *
 * @apiDescription This path change the priority of a ticket.
 *
 * @apiPermission Staff Level 1
 *
 * @apiParam {number} ticketNumber The number of a ticket.
 *
 * @apiParam {string} priority The new priority of the ticket.
 *
 * @apiError {String} message
 *
 * @apiSuccess {Object} data
 *
 */

class ChangePriorityController extends Controller {
    const PATH = '/change-priority';
    const METHOD = 'POST';

    public function validations() {
        return [
            'permission' => 'staff_1',
            'requestData' => [
                'ticketNumber' => [
                    'validation' => DataValidator::validTicketNumber(),
                    'error' => ERRORS::INVALID_TICKET
                ],
                'priority' => [
                    'validation' => DataValidator::in(['low', 'medium', 'high']),
                    'error' => ERRORS::INVALID_PRIORITY
                ]
            ]
        ];
    }

    public function handler() {
        $ticketNumber = Controller::request('ticketNumber');
        $priority = Controller::request('priority');
        $ticket = Ticket::getByTicketNumber($ticketNumber);
        $user = Controller::getLoggedUser();

        if($ticket->owner && $user->id === $ticket->owner->id) {
            $ticket->priority = $priority;
            $ticket->unread = true;
            $event = Ticketevent::getEvent(Ticketevent::PRIORITY_CHANGED);
            $event->setProperties(array(
                'authorStaff' => Controller::getLoggedUser(), 
                'content' => $ticket->priority,
                'date' => Date::getCurrentDate()
            ));
            $ticket->addEvent($event);
            $ticket->store();

            Log::createLog('PRIORITY_CHANGED', $ticket->ticketNumber);
            Response::respondSuccess();
        } else {
            Response::respondError(ERRORS::NO_PERMISSION);
        }

    }
}


