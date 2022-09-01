const lmsDomain = window.location.origin

window.addEventListener('resize', (e) => {
    const thumbImages = document.querySelectorAll('.my_course_content_list .mc_content_list .thumb img')
    thumbImages.forEach(element => {
        element.style.cssText += `height: ${element.offsetWidth}px !important;`
    })
})

const awaitBlockMyOverviewLoaded = setInterval(() => {
    const divContentWrapper = document.querySelectorAll('.mc_content_list .ccn_mc_content_header_details')

    divContentWrapper.forEach(function (element) {
        const elementProgress = element.querySelector('.ccn_mc_progress')
        const elementInfoTeacher = element.querySelector('.lms_info_teacher')

        if (!elementProgress) {
            const elementNew = document.createElement('div')
            elementNew.setAttribute('class', 'ccn_mc_progress')
            elementNew.classList.add('lms_block_myoverview_text_progress')
            elementNew.style.cssText = 'margin-top: 8px; font-size: 12px; color: rgb(197 11 11); text-align: center;'
            elementNew.innerText = 'Chưa có nội dung'
            element.appendChild(elementNew)
        }

        if (!elementInfoTeacher) {
            const parentElementCourse = element.closest('.mc_content_list')
            const thumbImage = parentElementCourse.querySelector('.thumb img')
            const courseid = parentElementCourse.getAttribute('data-course-id')

            thumbImage.style.cssText += `height: ${thumbImage.offsetWidth}px !important;`
            
            const elementNew = document.createElement('div')
            elementNew.setAttribute('class', 'lms_info_teacher')
            elementNew.style.cssText += 'font-size: 12px; text-align: center; margin-bottom: 8px; color: #58bd02;'

            fetch(lmsDomain + '/blocks/myoverview/lms_api_get_teacher_of_course.php?courseid=' + courseid, {
                method: 'POST', // or 'PUT'
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify([]),
            })
                .then((response) => response.json())
                .then((dataRes) => {
                    if (dataRes.message == 'success') {
                        const teacher = dataRes.data
                        if (!Array.isArray(teacher) && Object.keys(teacher).length !== 0) {
                            elementNew.innerText = `${teacher.firstname} ${teacher.lastname}`
                        } else {
                            elementNew.innerText = `Chưa có giáo viên`
                        }

                        element.insertBefore(elementNew, element.firstChild);
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                })
        }
    })
}, 1000)

setTimeout(() => {
    clearInterval(awaitBlockMyOverviewLoaded)
}, 40000)