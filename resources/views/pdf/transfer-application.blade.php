<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <style>
        body {
            font-family: dejavusans, sans-serif;
            font-size: 9pt;
            color: #111;
        }

        .sinhala {
            font-family: kaputaunicode, sans-serif;
        }

        h1 {
            text-align: center;
            font-size: 14pt;
            margin-bottom: 4px;
        }

        h2 {
            font-size: 10pt;
            background-color: #eeeeee;
            padding: 6px;
            margin-top: 14px;
            margin-bottom: 6px;
        }

        .center {
            text-align: center;
        }

        .small {
            font-size: 8pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        th,
        td {
            border: 1px solid #777;
            padding: 5px;
            vertical-align: top;
            overflow-wrap: break-word;
        }

        th {
            background-color: #f4f4f4;
            text-align: left;
        }

        .label {
            width: 32%;
            font-weight: bold;
        }

        .signature {
            margin-top: 28px;
        }
    </style>
</head>

<body>
    <h1>Transfer Application</h1>

    <p class="center">
        {{ $application->cycle->name }}
    </p>

    <p class="small">
        <strong>Reference:</strong>
        {{ $application->reference_no }}
        &nbsp;&nbsp;
        <strong>NIC:</strong>
        {{ $nic }}
    </p>

    <h2>A. Personal Information</h2>

    <table>
        <tr>
            <td class="label">Full name</td>
            <td>{{ $application->full_name }}</td>
        </tr>

        <tr>
            <td class="label">Name with initials in Sinhala</td>
            <td class="sinhala">
                {{ $application->name_with_initials_sinhala ?: '-' }}
            </td>
        </tr>

        <tr>
            <td class="label">Name with initials in English</td>
            <td>{{ $application->name_with_initials_english }}</td>
        </tr>

        <tr>
            <td class="label">Date of birth</td>
            <td>{{ $application->date_of_birth?->format('Y-m-d') }}</td>
        </tr>

        <tr>
            <td class="label">Sex</td>
            <td>{{ ucfirst($application->gender ?? '') }}</td>
        </tr>

        <tr>
            <td class="label">Permanent address</td>
            <td>{{ $application->permanent_address }}</td>
        </tr>

        <tr>
            <td class="label">Temporary address</td>
            <td>{{ $application->temporary_address ?: '-' }}</td>
        </tr>

        <tr>
            <td class="label">Office telephone</td>
            <td>{{ $application->office_phone ?: '-' }}</td>
        </tr>

        <tr>
            <td class="label">Personal telephone</td>
            <td>{{ $application->personal_phone }}</td>
        </tr>

        <tr>
            <td class="label">Marital status</td>
            <td>{{ ucfirst($application->marital_status ?? '') }}</td>
        </tr>

        <tr>
            <td class="label">Spouse’s name</td>
            <td>{{ $application->spouse_name ?: '-' }}</td>
        </tr>

        <tr>
            <td class="label">Spouse’s occupation</td>
            <td>{{ $application->spouse_occupation ?: '-' }}</td>
        </tr>

        <tr>
            <td class="label">Spouse’s workplace</td>
            <td>{{ $application->spouse_workplace ?: '-' }}</td>
        </tr>
    </table>

    <h2>Children</h2>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Age</th>
                <th>School</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($application->children as $child)
                <tr>
                    <td>{{ $child->name }}</td>
                    <td>{{ $child->age ?? '-' }}</td>
                    <td>{{ $child->school ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No children recorded.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>B. Service Information</h2>

    <table>
        <tr>
            <td class="label">Post</td>
            <td>{{ $application->post }}</td>
        </tr>

        <tr>
            <td class="label">Grade</td>
            <td>{{ $application->grade }}</td>
        </tr>

        <tr>
            <td class="label">Appointment letter number</td>
            <td>{{ $application->appointment_letter_no }}</td>
        </tr>

        <tr>
            <td class="label">Confirmed in post</td>
            <td>{{ $application->is_confirmed ? 'Yes' : 'No' }}</td>
        </tr>

        <tr>
            <td class="label">Current office</td>
            <td>{{ $application->currentOffice?->name }}</td>
        </tr>

        <tr>
            <td class="label">Service</td>
            <td>{{ $application->service?->name }}</td>
        </tr>

        <tr>
            <td class="label">Date reported to current office</td>
            <td>
                {{ $application->reported_to_current_office_on?->format('Y-m-d') }}
            </td>
        </tr>
    </table>

    <h2>Previous Workplaces</h2>

    <table>
        <thead>
            <tr>
                <th>Workplace</th>
                <th>From</th>
                <th>To</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($application->workHistory as $history)
                <tr>
                    <td>{{ $history->workplace_name }}</td>
                    <td>{{ $history->from_date?->format('Y-m-d') ?: '-' }}</td>
                    <td>{{ $history->to_date?->format('Y-m-d') ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No previous workplaces recorded.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>C. Preferred Offices</h2>

    <table>
        <thead>
            <tr>
                <th>Priority</th>
                <th>Office</th>
                <th>Reason</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($application->preferences as $preference)
                <tr>
                    <td>{{ $preference->priority }}</td>
                    <td>{{ $preference->office?->name }}</td>
                    <td>{{ $preference->reason ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p>
        <strong>Willing to accept a nearby office:</strong>
        {{ $application->accept_nearby_office ? 'Yes' : 'No' }}
    </p>

    <p>
        <strong>General reason for transfer:</strong><br>
        {{ $application->transfer_reason }}
    </p>

    <h2>Applicant’s Declaration</h2>

    <p>
        I declare that the information provided in this application is
        true and accurate.
    </p>

    <table class="signature">
        <tr>
            <td>Date: ........................................</td>
            <td>Applicant’s signature: ........................................</td>
        </tr>
    </table>

    <h2>D. Recommendation of the Head of Institution</h2>

    <p>
        I have checked the applicant’s information against the available
        official records.
    </p>

    <p>
        Recommendation: ....................................................................................
    </p>

    <p>
        Replacement requirement: ........................................................................
    </p>

    <table class="signature">
        <tr>
            <td>Date: ........................................</td>
            <td>
                Signature and official seal:
                ........................................
            </td>
        </tr>
    </table>
</body>
</html>
