<script setup lang="ts"></script>

<template>
    <div class="mt-6 w-2/3 overflow-hidden rounded bg-white shadow">
        <div class="flex flex-col p-4">
            <div class="flex items-center">
                <div class="w-8">
                    <img
                        src="https://cdn.pixabay.com/photo/2014/07/09/10/04/man-388104_960_720.jpg"
                        alt="profile"
                        class="h-8 w-8 rounded-full object-cover"
                    />
                </div>
                <div class="ml-6">
                    <div class="text-sm font-bold">Name User</div>
                    <div class="text-sm text-gray-300">12 mins</div>
                </div>
            </div>
            <div class="mt-4">
                <p>not having fun at all</p>
            </div>
        </div>
        <div class="w-full">
            <img
                src="https://zc.zufedfc.edu.cn/images/n_banner.jpg"
                alt=""
                class="w-full"
            />
        </div>

        <div class="flex justify-between gap-4 px-4 py-2 text-sm text-gray-500">
            <p>1.2K Likes</p>
            <p>300 Comments</p>
        </div>

        <div class="m-4 flex justify-between">
            <button
                class="flex w-full justify-center gap-1 rounded-lg py-4 text-sm hover:bg-gray-400"
            >
                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 16 16"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M2.08984 15C2.64213 15 3.08984 14.5523 3.08984 14L3.08984 8C3.08984 7.44771 2.64213 7 2.08984 7C1.53756 7 1.08984 7.44771 1.08984 8L1.08984 14C1.08984 14.5523 1.53756 15 2.08984 15Z"
                        fill="black"
                    />
                    <path
                        d="M5.7651 13H4.08965L4.08966 8C4.75253 8 5.30843 7.5336 5.64642 6.96337C5.97981 6.40091 6.44708 5.92717 7.00433 5.58603C7.48206 5.29357 7.91095 4.8797 7.99274 4.32557L7.99335 4.32132C8.03083 4.06658 8.05764 3.80838 8.0733 3.54717C8.08415 3.36621 8.08966 3.18369 8.08966 3C8.08966 2.55113 8.0568 2.10993 7.99335 1.67868C7.96492 1.48544 8.04119 1.28532 8.21687 1.19998C8.48062 1.07186 8.77674 1 9.08966 1C10.1942 1 11.0897 1.89555 11.0897 3.00012C11.0897 3.81187 11.0091 4.6048 10.8554 5.37133C10.7912 5.69168 11.0299 6 11.3566 6L13.0897 6C14.1942 6 15.1001 6.89759 14.9911 7.99676C14.9707 8.20221 14.9472 8.40675 14.9207 8.61035C14.7327 10.0566 14.3898 11.4534 13.9098 12.7844C13.6401 13.5322 12.9118 14 12.1169 14H9.41421C9.19925 14 8.98569 13.9653 8.78175 13.8974L6.39756 13.1026C6.19362 13.0347 5.98006 13 5.7651 13Z"
                        fill="black"
                    />
                </svg>

                <p>Like</p>
            </button>
            <button
                class="flex w-full justify-center gap-1 rounded-lg py-4 text-sm hover:bg-gray-400"
            >
                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 16 16"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        fill-rule="evenodd"
                        clip-rule="evenodd"
                        d="M1 8.74053C1 9.72321 1.71341 10.5653 2.68906 10.6827C3.5937 10.7915 4.50678 10.8729 5.42746 10.926C5.78969 10.9469 6.11506 11.1572 6.27733 11.4817L7.32918 13.5854C7.45622 13.8395 7.71592 14 8 14C8.28408 14 8.54378 13.8395 8.67082 13.5854L9.72265 11.4817C9.88492 11.1572 10.2103 10.9469 10.5725 10.9261C11.4932 10.873 12.4063 10.7916 13.3109 10.6828C14.2866 10.5654 15 9.72332 15 8.74062V4.25938C15 3.27668 14.2866 2.43458 13.3109 2.3172C11.57 2.10777 9.79777 2 8.00039 2C6.20273 2 4.43025 2.10781 2.68906 2.3173C1.71341 2.43469 1 3.27679 1 4.25947V8.74053ZM4 5.25C4 4.83579 4.33579 4.5 4.75 4.5H11.25C11.6642 4.5 12 4.83579 12 5.25C12 5.66421 11.6642 6 11.25 6H4.75C4.33579 6 4 5.66421 4 5.25ZM4.75 7C4.33579 7 4 7.33579 4 7.75C4 8.16421 4.33579 8.5 4.75 8.5H7.25C7.66421 8.5 8 8.16421 8 7.75C8 7.33579 7.66421 7 7.25 7H4.75Z"
                        fill="black"
                    />
                </svg>
                <p>Comment</p>
            </button>
        </div>
    </div>
</template>

<style scoped></style>
