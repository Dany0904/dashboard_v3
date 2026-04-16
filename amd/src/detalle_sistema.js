/* eslint-disable no-console */

define([
  "jquery",
  "core/ajax",
  "local_dashboard_v3/apexcharts"
], function ($, Ajax, ApexCharts) {

  return {
    init: function () {

      const urlParams = new URLSearchParams(window.location.search);
      let days = parseInt(urlParams.get("days")) || 30;

      // =========================
      // FILTRO DÍAS
      // =========================
      const filterDays = document.getElementById("filter-days");

      if (filterDays) {
        filterDays.addEventListener("change", function () {
          const url = new URL(window.location.href);
          url.searchParams.set("days", this.value);
          window.location.href = url.toString();
        });
      }

      // =========================
      // AJAX
      // =========================
      const requests = Ajax.call([
        {
          methodname: "local_dashboard_v3_get_system_performance_chart",
          args: { days: days }
        }
      ]);

      $.when(requests[0]).done(function (res) {

        let labels = [];
        let requestsData = [];
        let responseData = [];
        let errorsData = [];

        res.data.forEach(item => {
          labels.push(item.label);
          requestsData.push(item.requests);
          responseData.push(item.response);
          errorsData.push(item.errors);
        });

        if (window.systemPerformanceChart &&
            typeof window.systemPerformanceChart.destroy === "function") {
          window.systemPerformanceChart.destroy();
        }

        const options = {
          chart: {
            type: "line",
            height: 380,
            toolbar: { show: false }
          },
          series: [
            {
              name: "Requests",
              data: requestsData
            },
            {
              name: "Tiempo respuesta (ms)",
              data: responseData
            },
            {
              name: "Errores",
              data: errorsData
            }
          ],
          stroke: {
            curve: "smooth",
            width: [2, 2, 2]
          },
          xaxis: {
            categories: labels
          },
          colors: ["#4e73df", "#1cc88a", "#e74a3b"],
          tooltip: {
            shared: true,
            intersect: false
          },
          legend: {
            position: "top"
          },
          grid: {
            borderColor: "#e9ecef"
          }
        };

        window.systemPerformanceChart = new ApexCharts(
          document.querySelector("#systemPerformanceChart"),
          options
        );

        window.systemPerformanceChart.render();

      }).fail(function (err) {
        console.error("Error sistema:", err);
      });

    }
  };

});