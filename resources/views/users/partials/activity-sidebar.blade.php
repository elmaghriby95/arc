<div class="card card-elevated profile-activity-card">
    <div class="card-header">
        <div>
            <h3 class="card-title">{{ __('profile.activity_log') }}</h3>
            <p class="card-subtitle">{{ __('profile.activity_log_desc') }}</p>
        </div>
    </div>
    <div class="card-body profile-activity-body">
        @if ($activities->isEmpty())
            <div class="profile-activity-empty">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                <p>{{ __('profile.no_activity') }}</p>
            </div>
        @else
            <ul class="profile-activity-timeline">
                @foreach ($activities as $activity)
                    <li class="profile-activity-item profile-activity-item--{{ $activity['icon'] }}">
                        <div class="profile-activity-icon">
                            @include('profile.partials.activity-icon', ['icon' => $activity['icon']])
                        </div>
                        <div class="profile-activity-content">
                            <div class="profile-activity-header">
                                <strong>{{ $activity['label'] }}</strong>
                                <time datetime="{{ $activity['occurred_at']->toIso8601String() }}">
                                    {{ $activity['occurred_at']->diffForHumans() }}
                                </time>
                            </div>
                            @if ($activity['description'])
                                <p class="profile-activity-desc">
                                    @if ($activity['url'])
                                        <a href="{{ $activity['url'] }}">{{ $activity['description'] }}</a>
                                    @else
                                        {{ $activity['description'] }}
                                    @endif
                                </p>
                            @endif
                            @if ($activity['meta'])
                                <span class="profile-activity-meta">{{ $activity['meta'] }}</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
