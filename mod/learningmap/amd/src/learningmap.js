import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';
import placestore from 'mod_learningmap/placestore';

export const init = () => {
    // Load the needed template on startup for better execution speed.
    Templates.prefetchTemplates(['mod_learningmap/cssskeleton']);

    // Variable for storing the mouse offset
    var offset;

    // Variables for storing the paths that need update of the first (upd1) or
    // the second (upd2) coordinates.
    var upd1, upd2;

    // Variables for handling the currently selected elements
    var selectedElement = null,
        firstPlace = null,
        secondPlace = null,
        lastTarget = null;

    // Variable for storing the selected element for the activity selector
    var elementForActivitySelector = null;

    // DOM nodes for the editor
    let mapdiv = document.getElementById('learningmap-editor-map');
    let code = document.getElementById('id_introeditor_text');
    let colorChooserPlace = document.getElementById('learningmap-color-place');
    let colorChooserVisited = document.getElementById('learningmap-color-visited');
    let colorChooserPath = document.getElementById('learningmap-color-path');


    // DOM nodes for the activity selector
    let activitySetting = document.getElementById('learningmap-activity-setting');
    let activitySelector = document.getElementById('learningmap-activity-selector');
    let activityStarting = document.getElementById('learningmap-activity-starting');
    let activityTarget = document.getElementById('learningmap-activity-target');
    let advancedSettingsIcon = document.getElementById('learningmap-advanced-settings-icon');

    // Hide tree view as there is no preview file we can attach to
    let treeView = document.querySelector('.fp-viewbar .fp-vb-tree');
    if (treeView) {
        treeView.setAttribute('style', 'display: none;');
    }

    // Trigger click event on icon view to ensure that tree view is not active.
    let iconView = document.querySelector('.fp-viewbar .fp-vb-icons');
    if (iconView) {
        // Handle possible delay in form loading.
        setTimeout(() => {
            iconView.dispatchEvent(new Event('click'));
        }, 1000);
    }

    // Attach listeners to the activity selector
    if (activitySelector) {
        // Show places that are not linked to an activity
        activitySelector.addEventListener('change', function() {
            placestore.setActivityId(elementForActivitySelector, activitySelector.value);
            if (activitySelector.value) {
                document.getElementById(elementForActivitySelector).classList.remove('learningmap-emptyplace');
            } else {
                document.getElementById(elementForActivitySelector).classList.add('learningmap-emptyplace');
            }
            updateActivities();
            updateCode();
        });
        // Add / remove a place to the starting places array
        activityStarting.addEventListener('change', function() {
            if (activityStarting.checked) {
                placestore.addStartingPlace(elementForActivitySelector);
            } else {
                placestore.removeStartingPlace(elementForActivitySelector);
            }
            updateCode();
        });
        // Add / remove a place to the target places array
        activityTarget.addEventListener('change', function() {
            if (activityTarget.checked) {
                placestore.addTargetPlace(elementForActivitySelector);
                document.getElementById(elementForActivitySelector).classList.add('learningmap-targetplace');
            } else {
                placestore.removeTargetPlace(elementForActivitySelector);
                document.getElementById(elementForActivitySelector).classList.remove('learningmap-targetplace');
            }
            updateCode();
        });
    }

    // Load placestore values from the hidden input field
    let placestoreInput = document.getElementsByName('placestore')[0];
    if (placestoreInput) {
        placestore.loadJSON(placestoreInput.value);
    }

    // Mark all activities in the placestore as "used".
    updateActivities();

    // Attach listeners to the advanced settings div
    if (advancedSettingsIcon) {
        let advancedSettings = document.getElementById('learningmap-advanced-settings');
        advancedSettingsIcon.addEventListener('click', function() {
            advancedSettings.removeAttribute('hidden');
        });
        let advancedSettingsClose = document.getElementById('learningmap-advanced-settings-close');
        if (advancedSettingsClose) {
            advancedSettingsClose.addEventListener('click', function() {
                advancedSettings.setAttribute('hidden', '');
            });
        }

        let hidepaths = document.getElementById('learningmap-hidepaths');
        // Attach a listener to the hidepaths checkbox
        if (hidepaths) {
            if (placestore.getHidePaths()) {
                hidepaths.checked = true;
            }
            hidepaths.addEventListener('change', function() {
                if (hidepaths.checked) {
                    placestore.setHidePaths(true);
                } else {
                    placestore.setHidePaths(false);
                }
                updateCSS();
            });
        }

        let usecheckmark = document.getElementById('learningmap-usecheckmark');
        // Attach a listener to the usecheckmark checkbox
        if (usecheckmark) {
            if (placestore.getUseCheckmark()) {
                usecheckmark.checked = true;
            }
            usecheckmark.addEventListener('change', function() {
                if (usecheckmark.checked) {
                    placestore.setUseCheckmark(true);
                } else {
                    placestore.setUseCheckmark(false);
                }
                updateCSS();
            });
        }
}

    // Attach listener to the color choosers for paths
    if (colorChooserPath) {
        colorChooserPath.addEventListener('change', function() {
            placestore.setColor('stroke', colorChooserPath.value);
            updateCSS();
        });
        colorChooserPath.value = placestore.getColor('stroke');
    }

    // Attach listener to the color choosers for places
    if (colorChooserPlace) {
        colorChooserPlace.addEventListener('change', function() {
            placestore.setColor('place', colorChooserPlace.value);
            updateCSS();
        });
        colorChooserPlace.value = placestore.getColor('place');
    }

    // Attach listener to the color choosers for visited places
    if (colorChooserVisited) {
        colorChooserVisited.addEventListener('change', function() {
            placestore.setColor('visited', colorChooserVisited.value);
            updateCSS();
        });
        colorChooserVisited.value = placestore.getColor('visited');
    }

    // Get SVG code from the (hidden) textarea field
    if (code && mapdiv) {
        mapdiv.innerHTML = code.value;
    }
    // Reload background image to get the correct width and height values
    refreshBackgroundImage();
    registerBackgroundListener();
    updateCode();

    // Enable dragging of places
    let svg = document.getElementById('learningmap-svgmap-' + placestore.getMapid());
    makeDraggable(svg);

    // Refresh stylesheet values from placestore
    updateCSS();

    // Add listeners for clicking and context menu
    if (mapdiv) {
        mapdiv.addEventListener('dblclick', dblclickHandler);
        mapdiv.addEventListener('click', clickHandler);

        mapdiv.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            showContextMenu(e);
        }, false);
    }
    /**
     * Shows the context menu at the current mouse position
     * @param {*} e
     */
    function showContextMenu(e) {
        unselectAll();
        if (activitySetting) {
            if (e.target.classList.contains('learningmap-place')) {
                e.target.classList.add('learningmap-selected-activity-selector');
                let activityId = placestore.getActivityId(e.target.id);
                let cy = e.offsetY;
                let cx = e.offsetX;
                let vertical = 'top';
                let height = svg.getBoundingClientRect().height;
                let width = svg.getBoundingClientRect().width;
                if (cy > height / 2) {
                    vertical = 'bottom';
                    cy = height - cy;
                }
                let horizontal = 'left';
                if (cx > width / 2) {
                    horizontal = 'right';
                    cx = width - cx;
                }
                activitySetting.setAttribute('style', vertical + ': ' + cy + 'px; ' + horizontal + ': ' + cx + 'px;');
                activitySetting.removeAttribute('hidden');
                document.getElementById('learningmap-activity-selector').value = activityId;
                if (placestore.isStartingPlace(e.target.id)) {
                    document.getElementById('learningmap-activity-starting').checked = true;
                } else {
                    document.getElementById('learningmap-activity-starting').checked = false;
                }
                if (placestore.isTargetPlace(e.target.id)) {
                    document.getElementById('learningmap-activity-target').checked = true;
                } else {
                    document.getElementById('learningmap-activity-target').checked = false;
                }
                elementForActivitySelector = e.target.id;
            } else {
                hideContextMenu();
            }
        }
    }

    /**
     * Hides the context menu
     */
    function hideContextMenu() {
        let e = document.getElementById(elementForActivitySelector);
        if (e) {
            e.classList.remove('learningmap-selected-activity-selector');
        }
        activitySetting.setAttribute('hidden', '');
    }

    let backgroundfileNode = document.getElementById('id_introeditor_itemid_fieldset');
    if (backgroundfileNode) {
        let observer = new MutationObserver(refreshBackgroundImage);
        observer.observe(backgroundfileNode, {attributes: true, childList: true, subtree: true});
    }
    /**
     * Enables dragging on an DOM node
     * @param {*} el
     */
    function makeDraggable(el) {
        if (el) {
            el.addEventListener('mousedown', startDrag);
            el.addEventListener('mousemove', drag);
            el.addEventListener('mouseup', endDrag);
            el.addEventListener('mouseleave', endDrag);
            el.addEventListener('touchstart', startDrag);
            el.addEventListener('touchmove', drag);
            el.addEventListener('touchend', endDrag);
            el.addEventListener('touchleave', endDrag);
            el.addEventListener('touchcancel', endDrag);
        }

        /**
         * Helper function for getting the right coordinates from the mouse
         * @param {*} evt
         * @returns {object}
         */
        function getMousePosition(evt) {
            var CTM = el.getScreenCTM();
            if (evt.touches) {
                evt = evt.touches[0];
            }
            return {
                x: (evt.clientX - CTM.e) / CTM.a,
                y: (evt.clientY - CTM.f) / CTM.d
            };
        }

        /**
         * Function called whenn dragging starts.
         * @param {*} evt
         */
        function startDrag(evt) {
            evt.preventDefault();
            if (evt.target.classList.contains('learningmap-draggable')) {
                selectedElement = evt.target;
                offset = getMousePosition(evt);
                offset.x -= parseInt(selectedElement.getAttributeNS(null, "cx"));
                offset.y -= parseInt(selectedElement.getAttributeNS(null, "cy"));
                // Get paths that need to be updated.
                upd1 = placestore.getPathsWithFid(selectedElement.id);
                upd2 = placestore.getPathsWithSid(selectedElement.id);
            }
        }

        /**
         * Function called during dragging. Continuously updates circles center coordinates and the
         * coordinates of the touching paths.
         * @param {*} evt
         */
        function drag(evt) {
            evt.preventDefault();
            if (selectedElement) {
                var coord = getMousePosition(evt);
                let cx = coord.x - offset.x;
                let cy = coord.y - offset.y;
                selectedElement.setAttributeNS(null, "cx", cx);
                selectedElement.setAttributeNS(null, "cy", cy);

                upd1.forEach(function(p) {
                    let d = document.getElementById(p.id);
                    if (!(d === null)) {
                        if (d.nodeName == 'path') {
                            let pathDeclaration = d.getAttribute('d');
                            let newPathDeclaration = 'M ' + cx + ' ' + cy + ' L' + pathDeclaration.split('L')[1];
                            d.setAttribute('d', newPathDeclaration);
                        } else {
                            d.setAttribute('x1', cx);
                            d.setAttribute('y1', cy);
                        }
                    }
                });

                upd2.forEach(function(p) {
                    let d = document.getElementById(p.id);
                    if (!(d === null)) {
                        if (d.nodeName == 'path') {
                            let pathDeclaration = d.getAttribute('d');
                            let newPathDeclaration = pathDeclaration.split('L')[0] + 'L ' + cx + ' ' + cy;
                            d.setAttribute('d', newPathDeclaration);
                        } else {
                            d.setAttribute('x2', cx);
                            d.setAttribute('y2', cy);
                        }
                    }
                });
            }
        }

        /**
         * Function called when dragging ends.
         * @param {*} evt
         */
        function endDrag(evt) {
            evt.preventDefault();
            selectedElement = null;
            unselectAll();
            updateCode();
        }
    }

    /**
     * Updates the form fields for the SVG code and the placestore from the editor.
     */
    function updateCode() {
        if (code && mapdiv) {
            code.innerHTML = mapdiv.innerHTML;
        }
        if (placestoreInput) {
            document.getElementsByName('placestore')[0].value = JSON.stringify(placestore.getPlacestore());
        }
    }

    /**
     * Handles double clicks on the map
     * @param {*} event
     */
    function dblclickHandler(event) {
        hideContextMenu();
        unselectAll();
        if (event.target.classList.contains('learningmap-mapcontainer') ||
            event.target.classList.contains('learningmap-background-image')) {
            addPlace(event);
        } else if (event.target.classList.contains('learningmap-place')) {
            if (lastTarget == event.target.id) {
                lastTarget = null;
                clickHandler(event);
            } else {
                removePlace(event);
            }
        } else if (event.target.classList.contains('learningmap-path')) {
            removePath(event.target.id);
        }
        updateCode();
    }

    /**
     * Returns an empty title tag with the given id.
     * @param {*} id id for the title
     * @returns {any}
     */
    function title(id) {
        let title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
        title.setAttribute('id', id);
        return title;
    }

    /**
     * Returns a circle tag with the given dimensions.
     * @param {*} x x coordinate of the center
     * @param {*} y y coordinate of the center
     * @param {*} r radius
     * @param {*} classes classes to add
     * @param {*} id id of the circle
     * @returns {any}
     */
    function circle(x, y, r, classes, id) {
        let circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('class', classes);
        circle.setAttribute('id', id);
        circle.setAttribute('cx', x);
        circle.setAttribute('cy', y);
        circle.setAttribute('r', r);
        return circle;
    }

    /**
     * Returns a path between two points.
     * @param {*} x1 x coordinate of the first point
     * @param {*} y1 y coordinate of the first point
     * @param {*} x2 x coordinate of the second point
     * @param {*} y2 y coordinate of the second point
     * @param {*} classes CSS classes to set
     * @param {*} id id of the path
     * @returns {any}
     */
     function path(x1, y1, x2, y2, classes, id) {
        let path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('class', classes);
        path.setAttribute('id', id);
        path.setAttribute('d', 'M ' + x1 + ' ' + y1 + ' L ' + x2 + ' ' + y2);
        return path;
    }

    /**
     * Returns a link around a given child element. This function also adds a title element next
     * to the child for accessibility.
     * @param {*} child child item to set the link on
     * @param {*} id id of the link
     * @param {*} title title of the link
     * @returns {any}
     */
    function link(child, id, title = null) {
        let link = document.createElementNS('http://www.w3.org/2000/svg', 'a');
        link.setAttribute('id', id);
        link.setAttribute('xlink:href', '');
        link.appendChild(child);
        if (!(title === null)) {
            link.appendChild(title);
        }
        return link;
    }

    /**
     * Adds a place on the SVG map. This function also prepares the code for linking activities
     * and adding titles (for accessibility).
     * @param {*} event event causing the command
     */
    function addPlace(event) {
        let placesgroup = document.getElementById('placesGroup');
        let placeId = 'p' + placestore.getId();
        let linkId = 'a' + placestore.getId();
        var CTM = event.target.getScreenCTM();
        if (event.touches) {
            event = event.touches[0];
        }
        let cx = (event.clientX - CTM.e) / CTM.a;
        let cy = (event.clientY - CTM.f) / CTM.d;
        placesgroup.appendChild(
            link(
                circle(cx, cy, 10, 'learningmap-place learningmap-draggable learningmap-emptyplace', placeId),
                linkId,
                title('title' + placeId)
            )
        );
        placestore.addPlace(placeId, linkId);
    }

    /**
     * Handles single clicks on the background image.
     * @param {*} event click event
     * @returns {void}
     */
    function clickHandler(event) {
        event.preventDefault();
        hideContextMenu();
        if (event.target.classList.contains('learningmap-place') && selectedElement === null) {
            if (firstPlace === null) {
                firstPlace = event.target.id;
                document.getElementById(firstPlace).classList.add('learningmap-selected');
            } else {
                secondPlace = event.target.id;
                let fid = parseInt(firstPlace.replace('p', ''));
                let sid = parseInt(secondPlace.replace('p', ''));
                if (sid == fid) {
                    return;
                }
                if (sid < fid) {
                    let z = sid;
                    sid = fid;
                    fid = z;
                }
                addPath(fid, sid);
                let first = document.getElementById(firstPlace);
                if (first) {
                    first.classList.remove('learningmap-selected');
                }
                firstPlace = null;
                lastTarget = secondPlace;
                secondPlace = null;
            }
        } else {
            unselectAll();
            firstPlace = null;
        }
    }
    /**
     * Removes the classes 'learningmap-selected' and 'learningmap-selectet-activity-selector'
     * from all nodes
     */
    function unselectAll() {
        Array.from(document.getElementsByClassName('learningmap-selected')).forEach(function(e) {
            e.classList.remove('learningmap-selected');
        });
        Array.from(document.getElementsByClassName('learningmap-selected-activity-selector')).forEach(function(e) {
            e.classList.remove('learningmap-selected-activity-selector');
        });
    }

    /**
     * Adds a path between two places.
     * @param {number} fid id of the first place (meant to be the smaller one)
     * @param {number} sid id of the second place (meant to be the bigger one)
     */
    function addPath(fid, sid) {
        let pid = 'p' + fid + '_' + sid;
        if (document.getElementById(pid) === null) {
            let pathsgroup = document.getElementById('pathsGroup');
            let first = document.getElementById('p' + fid);
            let second = document.getElementById('p' + sid);
            if (pathsgroup && first && second) {
                pathsgroup.appendChild(
                    path(
                        first.cx.baseVal.value,
                        first.cy.baseVal.value,
                        second.cx.baseVal.value,
                        second.cy.baseVal.value,
                        'learningmap-path',
                        pid
                    )
                );
                placestore.addPath(pid, 'p' + fid, 'p' + sid);
            }
        }
    }

    /**
     * Removes a place from the SVG and the placestore. This function also removes all
     * touching paths and entries in statringplaces / targetplaces linking to the removed
     * place.
     * @param {any} event event causing the remove order
     */
    function removePlace(event) {
        let place = document.getElementById(event.target.id);
        let parent = place.parentNode;
        removePathsTouchingPlace(event.target.id);
        placestore.removePlace(event.target.id);
        parent.removeChild(place);
        parent.parentNode.removeChild(parent);

        updateCode();
    }

    /**
     * Removes all paths touching a certain place
     * @param {number} id id of the place
     */
    function removePathsTouchingPlace(id) {
        placestore.getTouchingPaths(id).forEach(
            function(e) {
                removePath(e.id);
            }
        );
    }

    /**
     * Removes a path from the SVG and from the placestore
     * @param {number} id id of the path
     */
    function removePath(id) {
        let path = document.getElementById(id);
        if (!(path === null)) {
            path.parentNode.removeChild(path);
            placestore.removePath(id);
        }
    }

    /**
     * Sets the background image of the SVG to the current image in filemanager.
     */
    function refreshBackgroundImage() {
        let previewimage = document.getElementsByClassName('realpreview');
        if (previewimage.length > 0) {
            let background = document.getElementById('learningmap-background-image');
            background.setAttribute('xlink:href', previewimage[0].getAttribute('src').split('?')[0]);
        }
    }

    /**
     * Adds an eventListener to the background image for watching file changes and updating
     * height and width of the image.
     */
    function registerBackgroundListener() {
        let background = document.getElementById('learningmap-background-image');
        if (background) {
            background.addEventListener('load', function() {
                background.removeAttribute('height');
                let height = parseInt(background.getBBox().height);
                let width = background.getBBox().width;
                placestore.setBackgroundDimensions(width, height);
                svg.setAttribute('viewBox', '0 0 ' + placestore.width + ' ' + placestore.height);
                background.setAttribute('width', width);
                background.setAttribute('height', height);
                updateCode();
            });
        }
    }

    /**
     * Updates CSS code inside the SVG (called, when one of the colors is changed).
     * Calls updateCode() when completed.
     */
    function updateCSS() {
        Templates.renderForPromise('mod_learningmap/cssskeleton', placestore.getPlacestore())
            .then(({html, js}) => {
                Templates.replaceNode('#learningmap-svgstyle', html, js);
                updateCode();
                return true;
            })
            .catch(ex => displayException(ex));
    }

    /**
     * Updates the activity selector to highlight the activities already used
     */
    function updateActivities() {
        let activities = placestore.getAllActivities();
        let options = Array.from(activitySelector.getElementsByTagName('option'));
        options.forEach(function(n) {
            if (activities.includes(n.value)) {
                n.classList.add('learningmap-used-activity');
            } else {
                n.classList.remove('learningmap-used-activity');
            }
        });
    }
};
