<?php
// =====================================================
// api/chat.php — API para el sistema de chat/soporte
// =====================================================
require_once '../config/supabase.php';

$action = $_GET['action'] ?? '';

switch ($action) {

  // Crear nuevo ticket de soporte
  case 'create_ticket':
    $body = json_decode(file_get_contents('php://input'), true);
    $data = [
      'cliente'  => trim($body['cliente']  ?? ''),
      'telefono' => trim($body['telefono'] ?? ''),
      'producto' => trim($body['producto'] ?? ''),
      'cantidad' => trim($body['cantidad'] ?? ''),
      'especial' => trim($body['especial'] ?? ''),
      'status'   => 'pending',
    ];
    if (!$data['cliente']) jsonResponse(['error' => 'Nombre requerido'], 400);
    $result = supabase('POST', 'chat_tickets', $data);
    if (!empty($result[0]['id'])) {
      jsonResponse(['success' => true, 'ticket_id' => $result[0]['id']]);
    }
    jsonResponse(['error' => 'Error al crear ticket'], 500);
    break;

  // Enviar mensaje
  case 'send_message':
    $body = json_decode(file_get_contents('php://input'), true);
    $data = [
      'ticket_id' => $body['ticket_id'] ?? '',
      'sender'    => $body['sender']    ?? 'user',
      'mensaje'   => trim($body['mensaje'] ?? ''),
    ];
    if (!$data['ticket_id'] || !$data['mensaje']) jsonResponse(['error' => 'Datos incompletos'], 400);
    $result = supabase('POST', 'chat_messages', $data);
    if (!empty($result[0]['id'])) {
      jsonResponse(['success' => true, 'message_id' => $result[0]['id']]);
    }
    jsonResponse(['error' => 'Error al enviar mensaje'], 500);
    break;

  // Admin reclama el ticket
  case 'claim_ticket':
    requireAdmin();
    $body = json_decode(file_get_contents('php://input'), true);
    $ticketId  = $body['ticket_id'] ?? '';
    $adminName = $body['admin_name'] ?? 'Admin';
    if (!$ticketId) jsonResponse(['error' => 'Ticket requerido'], 400);

    // Verificar que el ticket siga pendiente
    $ticket = supabase('GET', "chat_tickets?id=eq.$ticketId&status=eq.pending");
    if (empty($ticket)) jsonResponse(['error' => 'Ticket no disponible'], 409);

    $result = supabase('PATCH', "chat_tickets?id=eq.$ticketId", [
      'status'     => 'active',
      'admin_name' => $adminName,
      'updated_at' => date('c'),
    ]);
    jsonResponse(['success' => true]);
    break;

  // Admin cierra el ticket
  case 'close_ticket':
    requireAdmin();
    $body = json_decode(file_get_contents('php://input'), true);
    $ticketId = $body['ticket_id'] ?? '';
    if (!$ticketId) jsonResponse(['error' => 'Ticket requerido'], 400);
    supabase('PATCH', "chat_tickets?id=eq.$ticketId", [
      'status'     => 'closed',
      'updated_at' => date('c'),
    ]);
    jsonResponse(['success' => true]);
    break;

  // Cargar tickets (para el panel admin)
  case 'get_tickets':
    requireAdmin();
    $status = $_GET['status'] ?? '';
    $endpoint = 'chat_tickets?order=created_at.desc&limit=50';
    if ($status) $endpoint .= "&status=eq.$status";
    $tickets = supabase('GET', $endpoint);
    jsonResponse(['success' => true, 'tickets' => $tickets]);
    break;

  // Cargar mensajes de un ticket
  case 'get_messages':
    $ticketId = $_GET['ticket_id'] ?? '';
    if (!$ticketId) jsonResponse(['error' => 'Ticket requerido'], 400);
    $messages = supabase('GET', "chat_messages?ticket_id=eq.$ticketId&order=created_at.asc");
    jsonResponse(['success' => true, 'messages' => $messages]);
    break;

  // Contar tickets pendientes (para el badge del sidebar)
  case 'pending_count':
    $tickets = supabase('GET', 'chat_tickets?status=eq.pending&select=id');
    jsonResponse(['success' => true, 'count' => count($tickets)]);
    break;

  default:
    jsonResponse(['error' => 'Acción no válida'], 400);
}
