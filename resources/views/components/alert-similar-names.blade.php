<template x-if="Object.keys(similarNames).length > 0">
    <div role="alert" class="p-2 text-sm bg-red-100 border border-red-400">
        <p>
            <strong>
                {{ __('hiko.similar_name_exists') }}
            </strong>
        </p>
        <ul>
            <template x-for="identity, index in Object.keys(similarNames)" :key="index">
                <li x-text="similarNames[index].label"></li>
            </template>
        </ul>
    </div>
</template>
