<x-admin::layouts>
    <x-slot:title>
        @lang('aws::app.admin.layouts.aws')
    </x-slot>

    <div class="content">
        <form method="POST"action="" @submit.prevent="onSubmit">
            @csrf

            <div class="page-content">
                <accordian title="'{{ trans('aws::app.admin.aws-integration.storage-configuration') }}'"
                    :active="true">
                    <div slot="body">
                        <div class="flex gap-x-2.5 items-center">
                            <a href="{{ route('admin.aws.sync.assets') }}" class="primary-button">
                                @lang('aws::app.admin.aws-integration.synchronize')
                            </a>
                        </div>

                        <p class="mt-4">
                            <strong>
                                @lang('aws::app.admin.aws-integration.note')
                            </strong>
                            @lang('aws::app.admin.aws-integration.note-msg')
                        </p>
                    </div>
                </accordian>
            </div>
        </form>
    </div>
</x-admin::layouts>
