<?php
// Elaborado por GEMENI ASSIST y Alexis Gutierrez de www.ACTICVEN.COM
// ©2025. Software development and Authorized by WWW.ACTICVEN.COM All rights reserved.

/**
 * Genera el contenido de un archivo iCalendar (.ics) para una cita.
 *
 * @param array $details Un array asociativo con los detalles de la cita.
 *               - 'start_time': (string) Fecha y hora de inicio (ej. '2025-12-25 14:00:00').
 *               - 'end_time': (string) Fecha y hora de fin.
 *               - 'summary': (string) El título o asunto del evento.
 *               - 'description': (string) La descripción o notas del evento.
 *               - 'location': (string) La dirección del negocio.
 *               - 'organizer_email': (string) Email del organizador (negocio).
 *               - 'organizer_name': (string) Nombre del organizador (negocio).
 *               - 'attendee_email': (string) Email del asistente (cliente).
 *               - 'attendee_name': (string) Nombre del asistente (cliente).
 *               - 'uid': (string) Un identificador único para el evento.
 * @return string El contenido del archivo .ics.
 */
function generate_ical_content(array $details): string {
    // Asegurarse de que las fechas estén en formato UTC para iCalendar (YYYYMMDDTHHMMSSZ)
    $utc = new DateTimeZone('UTC');
    $dt_start = (new DateTime($details['start_time']))->setTimezone($utc)->format('Ymd\THis\Z');
    $dt_end = (new DateTime($details['end_time']))->setTimezone($utc)->format('Ymd\THis\Z');
    $dt_stamp = (new DateTime('now', $utc))->format('Ymd\THis\Z');

    // Escapar caracteres especiales para iCalendar
    $escape_chars = ['\\', ';', ',', "\n"];
    $replace_chars = ['\\\\', '\;', '\,', '\n'];
    $summary = str_replace($escape_chars, $replace_chars, $details['summary']);
    $description = str_replace($escape_chars, $replace_chars, $details['description']);
    $location = str_replace($escape_chars, $replace_chars, $details['location']);

    $ical = "BEGIN:VCALENDAR\r\n";
    $ical .= "VERSION:2.0\r\n";
    $ical .= "PRODID:-//ZAppCitas//ACTICVEN//ES\r\n";
    $ical .= "CALSCALE:GREGORIAN\r\n";
    $ical .= "METHOD:REQUEST\r\n";
    $ical .= "BEGIN:VEVENT\r\n";
    $ical .= "DTSTART:{$dt_start}\r\n";
    $ical .= "DTEND:{$dt_end}\r\n";
    $ical .= "DTSTAMP:{$dt_stamp}\r\n";
    $ical .= "UID:{$details['uid']}\r\n";
    $ical .= "ORGANIZER;CN=\"{$details['organizer_name']}\":mailto:{$details['organizer_email']}\r\n";
    $ical .= "ATTENDEE;CN=\"{$details['attendee_name']}\";ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE:mailto:{$details['attendee_email']}\r\n";
    
    // Añadir invitados adicionales si existen
    if (!empty($details['guests'])) {
        foreach ($details['guests'] as $guest) {
            if (!empty($guest['email'])) {
                $guest_name = $guest['name'] ?? 'Invitado';
                $ical .= "ATTENDEE;CN=\"{$guest_name}\";ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE:mailto:{$guest['email']}\r\n";
            }
        }
    }

    $ical .= "SUMMARY:{$summary}\r\n";
    $ical .= "DESCRIPTION:{$description}\r\n";
    $ical .= "LOCATION:{$location}\r\n";
    $ical .= "SEQUENCE:0\r\n";
    $ical .= "STATUS:CONFIRMED\r\n";
    $ical .= "TRANSP:OPAQUE\r\n";
    $ical .= "END:VEVENT\r\n";
    $ical .= "END:VCALENDAR\r\n";

    return $ical;
}
?>