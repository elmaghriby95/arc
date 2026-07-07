@props(['status'])

@php
    use App\Enums\LendingRequestStatus;

    $steps = [
        [
            'status' => LendingRequestStatus::PendingReview,
            'label' => LendingRequestStatus::PendingReview->label(),
            'hint' => __('lending_requests.workflow.pending_review_hint'),
        ],
        [
            'status' => LendingRequestStatus::PendingHandover,
            'label' => LendingRequestStatus::PendingHandover->label(),
            'hint' => __('lending_requests.workflow.pending_handover_hint'),
        ],
        [
            'status' => LendingRequestStatus::OnLoan,
            'label' => LendingRequestStatus::OnLoan->label(),
            'hint' => __('lending_requests.workflow.on_loan_hint'),
        ],
        [
            'status' => LendingRequestStatus::Returned,
            'label' => LendingRequestStatus::Returned->label(),
            'hint' => __('lending_requests.workflow.returned_hint'),
        ],
    ];

    $statusOrder = [
        LendingRequestStatus::PendingReview->value => 0,
        LendingRequestStatus::PendingHandover->value => 1,
        LendingRequestStatus::OnLoan->value => 2,
        LendingRequestStatus::Returned->value => 3,
        LendingRequestStatus::Rejected->value => -1,
    ];

    $currentEnum = $status instanceof LendingRequestStatus
        ? $status
        : ($status ? LendingRequestStatus::tryFrom((string) $status) : null);

    $currentIndex = $currentEnum ? ($statusOrder[$currentEnum->value] ?? -1) : -1;
    $isRejected = $currentEnum === LendingRequestStatus::Rejected;
@endphp

<div class="lending-workflow-steps">
    @foreach ($steps as $index => $step)
        @php
            $stepClass = 'lending-workflow-step';
            if ($isRejected) {
                $stepClass .= $index === 0 ? ' lending-workflow-step--done' : '';
            } elseif ($index < $currentIndex) {
                $stepClass .= ' lending-workflow-step--done';
            } elseif ($index === $currentIndex) {
                $stepClass .= ' lending-workflow-step--current';
            }
        @endphp
        <div class="{{ $stepClass }}">
            <span class="lending-workflow-step-num">{{ $index + 1 }}</span>
            <div>
                <span class="lending-workflow-step-label">{{ $step['label'] }}</span>
                <span class="lending-workflow-step-hint">{{ $step['hint'] }}</span>
            </div>
        </div>
    @endforeach

    @if ($isRejected)
        <div class="lending-workflow-step lending-workflow-step--rejected">
            <span class="lending-workflow-step-num">!</span>
            <div>
                <span class="lending-workflow-step-label">{{ LendingRequestStatus::Rejected->label() }}</span>
                <span class="lending-workflow-step-hint">{{ __('lending_requests.workflow.rejected_hint') }}</span>
            </div>
        </div>
    @endif
</div>
