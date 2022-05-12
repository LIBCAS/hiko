<template x-if="Object.keys(similarNames).length > 0">
    <div role="alert" class="p-2 text-sm bg-red-100 border border-red-400">
        <p>
            <strong>
                {{ __('hiko.similar_name_exists') }}
            </strong>
        </p>
        <ul>
            <template x-for="(item, index) in Object.values(similarNames)" :key="index">
                <li x-text="item.label"></li>
            </template>
        </ul>
    </div>
</template>
