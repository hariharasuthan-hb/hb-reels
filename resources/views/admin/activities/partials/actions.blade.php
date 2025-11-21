<div class="flex items-center gap-2">
    @if($canReviewVideos)
        <a href="{{ route('admin.workout-videos.index', ['status' => 'pending']) }}"
           class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors">
            Review Videos
        </a>
    @endif
    <a href="{{ route('admin.activities.index', ['member_id' => $log->user_id]) }}"
       class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">
        View Logs
    </a>
</div>

